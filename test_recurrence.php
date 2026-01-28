<?php
/**
 * 循环逻辑单元测试
 * 测试循环日期生成的各种边界情况
 */

require_once './recurrence_helper.php';

class RecurrenceTest {
    private $passed = 0;
    private $failed = 0;
    private $tests = [];
    
    public function run() {
        echo "循环逻辑单元测试\n";
        echo str_repeat('=', 60) . "\n\n";
        
        // 测试用例
        $this->testDailyRecurrence();
        $this->testWeeklyRecurrence();
        $this->testMonthlyRecurrence();
        $this->testYearlyRecurrence();
        $this->testEndTypeNever();
        $this->testEndTypeCount();
        $this->testEndTypeDate();
        $this->testMonthEnd();
        $this->testYearEnd();
        $this->testLeapYear();
        $this->testValidation();
        
        // 输出结果
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "测试结果: {$this->passed} 通过, {$this->failed} 失败\n";
        
        if ($this->failed > 0) {
            echo "\n失败的测试:\n";
            foreach ($this->tests as $test) {
                if (!$test['passed']) {
                    echo "  - {$test['name']}: {$test['error']}\n";
                }
            }
        }
        
        return $this->failed === 0;
    }
    
    private function assert($name, $condition, $error = '') {
        if ($condition) {
            $this->passed++;
            $this->tests[] = ['name' => $name, 'passed' => true];
            echo "✓ {$name}\n";
        } else {
            $this->failed++;
            $this->tests[] = ['name' => $name, 'passed' => false, 'error' => $error];
            echo "✗ {$name}\n";
        }
    }
    
    private function assertEquals($name, $expected, $actual, $error = '') {
        $condition = $expected === $actual;
        if (!$condition) {
            $error = $error ?: "期望: {$expected}, 实际: {$actual}";
        }
        $this->assert($name, $condition, $error);
    }
    
    private function testDailyRecurrence() {
        echo "\n测试每日循环:\n";
        
        $date1 = RecurrenceHelper::getNextDate('2024-01-01', 'daily', 1, 1);
        $this->assertEquals('每日循环第1次', '2024-01-02', $date1);
        
        $date2 = RecurrenceHelper::getNextDate('2024-01-01', 'daily', 2, 1);
        $this->assertEquals('每日循环间隔2', '2024-01-03', $date2);
        
        $date3 = RecurrenceHelper::getNextDate('2024-01-31', 'daily', 1, 1);
        $this->assertEquals('月底跨月', '2024-02-01', $date3);
    }
    
    private function testWeeklyRecurrence() {
        echo "\n测试每周循环:\n";
        
        $date1 = RecurrenceHelper::getNextDate('2024-01-01', 'weekly', 1, 1);
        $this->assertEquals('每周循环第1次', '2024-01-08', $date1);
        
        $date2 = RecurrenceHelper::getNextDate('2024-01-01', 'weekly', 2, 1);
        $this->assertEquals('每周循环间隔2', '2024-01-15', $date2);
    }
    
    private function testMonthlyRecurrence() {
        echo "\n测试每月循环:\n";
        
        $date1 = RecurrenceHelper::getNextDate('2024-01-15', 'monthly', 1, 1);
        $this->assertEquals('每月循环第1次', '2024-02-15', $date1);
        
        $date2 = RecurrenceHelper::getNextDate('2024-01-15', 'monthly', 2, 1);
        $this->assertEquals('每月循环间隔2', '2024-03-15', $date2);
        
        $date3 = RecurrenceHelper::getNextDate('2024-01-31', 'monthly', 1, 1);
        $this->assertEquals('1月31日下月', '2024-02-29', $date3);
    }
    
    private function testYearlyRecurrence() {
        echo "\n测试每年循环:\n";
        
        $date1 = RecurrenceHelper::getNextDate('2024-01-01', 'yearly', 1, 1);
        $this->assertEquals('每年循环第1次', '2025-01-01', $date1);
        
        $date2 = RecurrenceHelper::getNextDate('2024-01-01', 'yearly', 2, 1);
        $this->assertEquals('每年循环间隔2', '2026-01-01', $date2);
    }
    
    private function testEndTypeNever() {
        echo "\n测试永不结束:\n";
        
        $dates = RecurrenceHelper::generateAllDates('2024-01-01', 'daily', 1, 'never', null, null, 5);
        $this->assertEquals('永不结束生成5个日期', 5, count($dates));
    }
    
    private function testEndTypeCount() {
        echo "\n测试指定次数结束:\n";
        
        $dates = RecurrenceHelper::generateAllDates('2024-01-01', 'daily', 1, 'count', 3, null, 10);
        $this->assertEquals('指定3次生成3个日期', 3, count($dates));
        
        $dates = RecurrenceHelper::generateAllDates('2024-01-01', 'daily', 1, 'count', 10, null, 5);
        $this->assertEquals('指定10次但限制5个', 5, count($dates));
    }
    
    private function testEndTypeDate() {
        echo "\n测试指定日期结束:\n";
        
        $dates = RecurrenceHelper::generateAllDates('2024-01-01', 'daily', 1, 'date', null, '2024-01-05', 10);
        $this->assertEquals('到1月5日生成4个日期', 4, count($dates));
    }
    
    private function testMonthEnd() {
        echo "\n测试月底边界:\n";
        
        $date1 = RecurrenceHelper::getNextDate('2024-01-31', 'monthly', 1, 1);
        $this->assertEquals('1月31日->2月29日(闰年)', '2024-02-29', $date1);
        
        $date2 = RecurrenceHelper::getNextDate('2024-02-29', 'monthly', 1, 1);
        $this->assertEquals('2月29日->3月31日(保持月底)', '2024-03-31', $date2);
        
        $date3 = RecurrenceHelper::getNextDate('2023-01-31', 'monthly', 1, 1);
        $this->assertEquals('1月31日->2月28日(平年)', '2023-02-28', $date3);
        
        $date4 = RecurrenceHelper::getNextDate('2023-12-31', 'monthly', 1, 1);
        $this->assertEquals('12月31日->次年1月31日', '2024-01-31', $date4);
    }
    
    private function testYearEnd() {
        echo "\n测试年底边界:\n";
        
        $date1 = RecurrenceHelper::getNextDate('2023-12-31', 'yearly', 1, 1);
        $this->assertEquals('2023年底->2024年底', '2024-12-31', $date1);
    }
    
    private function testLeapYear() {
        echo "\n测试闰年:\n";
        
        $date1 = RecurrenceHelper::getNextDate('2024-02-29', 'yearly', 1, 1);
        $this->assertEquals('闰年2月29日->平年2月28日', '2025-02-28', $date1);
        
        $date2 = RecurrenceHelper::getNextDate('2024-02-29', 'yearly', 1, 4);
        $this->assertEquals('4年后又是闰年', '2028-02-29', $date2);
    }
    
    private function testValidation() {
        echo "\n测试规则验证:\n";
        
        $rule1 = [
            'recurrence_type' => 'weekly',
            'interval' => 1,
            'end_type' => 'never'
        ];
        $result1 = RecurrenceHelper::validateRule($rule1);
        $this->assert('有效规则验证', $result1['valid'], implode(', ', $result1['errors']));
        
        $rule2 = [
            'recurrence_type' => 'invalid',
            'interval' => 1,
            'end_type' => 'never'
        ];
        $result2 = RecurrenceHelper::validateRule($rule2);
        $this->assert('无效循环类型', !$result2['valid']);
        
        $rule3 = [
            'recurrence_type' => 'weekly',
            'interval' => 0,
            'end_type' => 'never'
        ];
        $result3 = RecurrenceHelper::validateRule($rule3);
        $this->assert('无效间隔(0)', !$result3['valid']);
        
        $rule4 = [
            'recurrence_type' => 'weekly',
            'interval' => 1,
            'end_type' => 'count',
            'end_count' => 0
        ];
        $result4 = RecurrenceHelper::validateRule($rule4);
        $this->assert('无效结束次数(0)', !$result4['valid']);
    }
}

// 运行测试
$test = new RecurrenceTest();
$success = $test->run();

exit($success ? 0 : 1);
?>
