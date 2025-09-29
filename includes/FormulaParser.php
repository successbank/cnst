<?php
/**
 * 안전한 수식 파서 클래스
 * eval()을 사용하지 않고 수식을 안전하게 파싱하고 계산합니다.
 */
class FormulaParser {
    private $constants = [];
    private $allowedFunctions = ['sqrt', 'pow', 'abs', 'round', 'ceil', 'floor', 'min', 'max'];

    /**
     * 수식을 파싱하고 계산합니다
     *
     * @param string $expression 수식 (예: "diameter * diameter * 0.00617 * length")
     * @param array $variables 변수 값 (예: ['diameter' => 10, 'length' => 8])
     * @return float 계산 결과
     * @throws Exception 파싱 오류 시
     */
    public function parse($expression, $variables = []) {
        // 1. 변수 치환
        $expression = $this->substituteVariables($expression, $variables);

        // 2. 문법 검증
        $this->validate($expression);

        // 3. 계산 실행
        return $this->evaluate($expression);
    }

    /**
     * 상수 설정
     */
    public function setConstants($constants) {
        $this->constants = $constants;
    }

    /**
     * 변수를 실제 값으로 치환
     */
    private function substituteVariables($expression, $variables) {
        // 상수 + 변수 통합
        $allVars = array_merge($this->constants, $variables);

        // 변수명을 길이순으로 정렬 (긴 변수명부터 치환)
        uksort($allVars, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($allVars as $name => $value) {
            // 변수명을 정확하게 매칭 (단어 경계 사용)
            $expression = preg_replace('/\b' . preg_quote($name, '/') . '\b/', $value, $expression);
        }

        return $expression;
    }

    /**
     * 수식 문법 검증
     */
    public function validate($expression) {
        // 위험한 문자열 검사
        $dangerous = ['eval', 'exec', 'system', 'shell_exec', 'passthru', 'proc_open', 'popen',
                      'curl_exec', 'curl_multi_exec', 'parse_ini_file', 'show_source', '__'];

        foreach ($dangerous as $danger) {
            if (stripos($expression, $danger) !== false) {
                throw new Exception("보안상 허용되지 않는 문자열: $danger");
            }
        }

        // 허용된 문자만 포함하는지 검사 (숫자, 연산자, 괄호, 공백, 소수점, e 표기법)
        if (!preg_match('/^[0-9+\-*\/().,\seE]+$/', $expression)) {
            throw new Exception("허용되지 않는 문자가 포함되어 있습니다: $expression");
        }

        // 괄호 짝 검사
        $openCount = substr_count($expression, '(');
        $closeCount = substr_count($expression, ')');
        if ($openCount !== $closeCount) {
            throw new Exception("괄호 짝이 맞지 않습니다");
        }

        return true;
    }

    /**
     * 수식 계산 (안전한 방식)
     */
    private function evaluate($expression) {
        // 공백 제거
        $expression = preg_replace('/\s+/', '', $expression);

        try {
            // 후위 표기법으로 변환 후 계산
            $result = $this->evaluatePostfix($this->toPostfix($expression));
            return $result;
        } catch (Exception $e) {
            throw new Exception("수식 계산 오류: " . $e->getMessage());
        }
    }

    /**
     * 중위 표기법을 후위 표기법으로 변환
     */
    private function toPostfix($expression) {
        $output = [];
        $stack = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2, '^' => 3];

        $tokens = $this->tokenize($expression);

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = $token;
            } elseif ($token === '(') {
                $stack[] = $token;
            } elseif ($token === ')') {
                while (!empty($stack) && end($stack) !== '(') {
                    $output[] = array_pop($stack);
                }
                if (!empty($stack)) {
                    array_pop($stack); // '(' 제거
                }
            } elseif (isset($precedence[$token])) {
                while (!empty($stack) &&
                       end($stack) !== '(' &&
                       isset($precedence[end($stack)]) &&
                       $precedence[end($stack)] >= $precedence[$token]) {
                    $output[] = array_pop($stack);
                }
                $stack[] = $token;
            }
        }

        while (!empty($stack)) {
            $output[] = array_pop($stack);
        }

        return $output;
    }

    /**
     * 수식을 토큰으로 분리
     */
    private function tokenize($expression) {
        $tokens = [];
        $number = '';

        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];

            if (is_numeric($char) || $char === '.') {
                $number .= $char;
            } else {
                if ($number !== '') {
                    $tokens[] = $number;
                    $number = '';
                }

                if ($char !== ' ') {
                    $tokens[] = $char;
                }
            }
        }

        if ($number !== '') {
            $tokens[] = $number;
        }

        return $tokens;
    }

    /**
     * 후위 표기법 계산
     */
    private function evaluatePostfix($postfix) {
        $stack = [];

        foreach ($postfix as $token) {
            if (is_numeric($token)) {
                $stack[] = floatval($token);
            } else {
                if (count($stack) < 2) {
                    throw new Exception("잘못된 수식 형식");
                }

                $b = array_pop($stack);
                $a = array_pop($stack);

                switch ($token) {
                    case '+':
                        $stack[] = $a + $b;
                        break;
                    case '-':
                        $stack[] = $a - $b;
                        break;
                    case '*':
                        $stack[] = $a * $b;
                        break;
                    case '/':
                        if ($b == 0) {
                            throw new Exception("0으로 나눌 수 없습니다");
                        }
                        $stack[] = $a / $b;
                        break;
                    case '^':
                        $stack[] = pow($a, $b);
                        break;
                    default:
                        throw new Exception("알 수 없는 연산자: $token");
                }
            }
        }

        if (count($stack) !== 1) {
            throw new Exception("수식 계산 결과가 올바르지 않습니다");
        }

        return $stack[0];
    }

    /**
     * 함수 호출 처리 (sqrt, pow 등)
     */
    private function executeFunction($funcName, $args) {
        $funcName = strtolower($funcName);

        if (!in_array($funcName, $this->allowedFunctions)) {
            throw new Exception("허용되지 않는 함수: $funcName");
        }

        switch ($funcName) {
            case 'sqrt':
                return sqrt($args[0]);
            case 'pow':
                return pow($args[0], $args[1]);
            case 'abs':
                return abs($args[0]);
            case 'round':
                return isset($args[1]) ? round($args[0], $args[1]) : round($args[0]);
            case 'ceil':
                return ceil($args[0]);
            case 'floor':
                return floor($args[0]);
            case 'min':
                return min($args);
            case 'max':
                return max($args);
            default:
                throw new Exception("구현되지 않은 함수: $funcName");
        }
    }
}