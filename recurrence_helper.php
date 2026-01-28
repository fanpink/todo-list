<?php
/**
 * 循环事件辅助类
 * 处理循环规则的生成、计算和验证
 */

class RecurrenceHelper {
    
    const TYPE_YEARLY = 'yearly';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_WEEKLY = 'weekly';
    const TYPE_DAILY = 'daily';
    
    const END_TYPE_NEVER = 'never';
    const END_TYPE_COUNT = 'count';
    const END_TYPE_DATE = 'date';
    
    /**
     * 生成下一个循环日期
     * 
     * @param string $startDate 起始日期 (YYYY-MM-DD)
     * @param string $recurrenceType 循环类型
     * @param int $interval 间隔
     * @param int $occurrence 当前是第几次
     * @return string 下一个日期 (YYYY-MM-DD)
     */
    public static function getNextDate($startDate, $recurrenceType, $interval, $occurrence = 0) {
        $date = new DateTime($startDate);
        $originalYear = (int)$date->format('Y');
        $originalMonth = (int)$date->format('m');
        $originalDay = (int)$date->format('d');
        $originalLastDay = (int)$date->format('t');
        $isOriginalLastDay = ($originalDay === $originalLastDay);
        
        switch ($recurrenceType) {
            case self::TYPE_YEARLY:
                $newYear = $originalYear + ($interval * $occurrence);
                $date->setDate($newYear, $originalMonth, 1);
                $newLastDay = (int)$date->format('t');
                if ($isOriginalLastDay) {
                    $date->setDate($newYear, $originalMonth, $newLastDay);
                } else {
                    $date->setDate($newYear, $originalMonth, min($originalDay, $newLastDay));
                }
                break;
            case self::TYPE_MONTHLY:
                $totalMonths = $originalYear * 12 + $originalMonth - 1 + ($interval * $occurrence);
                $newYear = (int)floor($totalMonths / 12);
                $newMonth = ($totalMonths % 12) + 1;
                $date->setDate($newYear, $newMonth, 1);
                $newLastDay = (int)$date->format('t');
                if ($isOriginalLastDay) {
                    $date->setDate($newYear, $newMonth, $newLastDay);
                } else {
                    $date->setDate($newYear, $newMonth, min($originalDay, $newLastDay));
                }
                break;
            case self::TYPE_WEEKLY:
                $date->modify('+' . ($interval * $occurrence) . ' weeks');
                break;
            case self::TYPE_DAILY:
                $date->modify('+' . ($interval * $occurrence) . ' days');
                break;
            default:
                throw new Exception("不支持的循环类型: {$recurrenceType}");
        }
        
        return $date->format('Y-m-d');
    }
    
    /**
     * 生成所有循环日期
     * 
     * @param string $startDate 起始日期
     * @param string $recurrenceType 循环类型
     * @param int $interval 间隔
     * @param string $endType 结束类型
     * @param int|null $endCount 结束次数
     * @param string|null $endDate 结束日期
     * @param int $maxLimit 最大生成数量限制
     * @return array 日期数组
     */
    public static function generateAllDates($startDate, $recurrenceType, $interval, $endType, $endCount = null, $endDate = null, $maxLimit = 100) {
        $dates = [];
        $occurrence = 0;
        
        while (true) {
            $occurrence++;
            $nextDate = self::getNextDate($startDate, $recurrenceType, $interval, $occurrence);
            
            if ($occurrence > $maxLimit) {
                break;
            }
            
            if ($endType === self::END_TYPE_COUNT && $endCount && $occurrence > $endCount) {
                break;
            }
            
            if ($endType === self::END_TYPE_DATE && $endDate && $nextDate > $endDate) {
                break;
            }
            
            $dates[] = [
                'date' => $nextDate,
                'occurrence' => $occurrence
            ];
        }
        
        return $dates;
    }
    
    /**
     * 检查是否应该生成下一个实例
     * 
     * @param int $currentCount 当前已生成的数量
     * @param string $endType 结束类型
     * @param int|null $endCount 结束次数
     * @param string|null $nextDate 下一个日期
     * @param string|null $endDate 结束日期
     * @return bool
     */
    public static function shouldGenerateNext($currentCount, $endType, $endCount = null, $nextDate = null, $endDate = null) {
        if ($endType === self::END_TYPE_NEVER) {
            return true;
        }
        
        if ($endType === self::END_TYPE_COUNT && $endCount) {
            return $currentCount < $endCount;
        }
        
        if ($endType === self::END_TYPE_DATE && $endDate && $nextDate) {
            return $nextDate <= $endDate;
        }
        
        return false;
    }
    
    /**
     * 验证循环规则
     * 
     * @param array $rule 循环规则
     * @return array 验证结果
     */
    public static function validateRule($rule) {
        $errors = [];
        
        if (empty($rule['recurrence_type'])) {
            $errors[] = '循环类型不能为空';
        } elseif (!in_array($rule['recurrence_type'], [self::TYPE_YEARLY, self::TYPE_MONTHLY, self::TYPE_WEEKLY, self::TYPE_DAILY])) {
            $errors[] = '无效的循环类型';
        }
        
        if (!isset($rule['interval']) || $rule['interval'] < 1) {
            $errors[] = '循环间隔必须大于0';
        }
        
        if (empty($rule['end_type'])) {
            $errors[] = '结束类型不能为空';
        } elseif (!in_array($rule['end_type'], [self::END_TYPE_NEVER, self::END_TYPE_COUNT, self::END_TYPE_DATE])) {
            $errors[] = '无效的结束类型';
        }
        
        if ($rule['end_type'] === self::END_TYPE_COUNT && (!isset($rule['end_count']) || $rule['end_count'] < 1)) {
            $errors[] = '结束次数必须大于0';
        }
        
        if ($rule['end_type'] === self::END_TYPE_DATE && empty($rule['end_date'])) {
            $errors[] = '结束日期不能为空';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * 获取循环类型的显示名称
     * 
     * @param string $type 循环类型
     * @return string
     */
    public static function getTypeName($type) {
        $names = [
            self::TYPE_YEARLY => '每年',
            self::TYPE_MONTHLY => '每月',
            self::TYPE_WEEKLY => '每周',
            self::TYPE_DAILY => '每天'
        ];
        return $names[$type] ?? $type;
    }
    
    /**
     * 获取结束类型的显示名称
     * 
     * @param string $type 结束类型
     * @return string
     */
    public static function getEndTypeName($type) {
        $names = [
            self::END_TYPE_NEVER => '永不结束',
            self::END_TYPE_COUNT => '指定次数',
            self::END_TYPE_DATE => '指定日期'
        ];
        return $names[$type] ?? $type;
    }
    
    /**
     * 格式化循环规则为可读字符串
     * 
     * @param array $rule 循环规则
     * @return string
     */
    public static function formatRule($rule) {
        $text = self::getTypeName($rule['recurrence_type']);
        
        if ($rule['interval'] > 1) {
            $text .= "，每{$rule['interval']}个周期";
        }
        
        $text .= '，' . self::getEndTypeName($rule['end_type']);
        
        if ($rule['end_type'] === self::END_TYPE_COUNT && $rule['end_count']) {
            $text .= "（{$rule['end_count']}次）";
        }
        
        if ($rule['end_type'] === self::END_TYPE_DATE && $rule['end_date']) {
            $text .= "（{$rule['end_date']}）";
        }
        
        return $text;
    }
}
?>
