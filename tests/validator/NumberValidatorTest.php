<?php
namespace esperecyan\dictionary_api\validator;

class NumberValidatorTest extends \PHPUnit_Framework_TestCase implements \Psr\Log\LoggerInterface
{
    use \esperecyan\dictionary_api\LogLevelLoggerTrait;
    
    /**
     * @param bool $realNumber
     * @param string $input
     * @param string $output
     * @dataProvider numberProvider
     */
    public function testCorrect(bool $realNumber, string $input, string $output)
    {
        $validator = new NumberValidator($realNumber);
        $validator->setLogger($this);
        $this->assertSame($output, $validator->correct($input));
        $this->assertSame($input === $output ? [] : [\Psr\Log\LogLevel::ERROR], $this->logLevels);
    }
    
    public function numberProvider(): array
    {
        return [
            [false, '0'     , '0'   ],
            [false, '0.0'   , '0'   ],
            [false, '0.9'   , '0'   ],
            [false, '-0'    , '0'   ],
            [false, '+0'    , '0'   ],
            [false, '10'    , '10'  ],
            [false, '-10'   , '-10' ],
            [false, '0700'  , '700' ],
            [false, '0x0010', '0'   ],
            [true , '0.2'   , '0.2' ],
            [true , '0.9999999999999999999999999999', '0.9999999999999999999999999999'],
            [true , '-5.6'  , '-5.6'],
            [true , '1.2e3' , '1200'],
            [true , 'test'  , '0'   ],
        ];
    }
}