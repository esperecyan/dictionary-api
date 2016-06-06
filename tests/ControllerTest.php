<?php
namespace esperecyan\dictionary_api;

/**
 * @runTestsInSeparateProcesses
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{
    /** @var int $_SERVER['CONTENT_LENGTH']未設定時の値。 */
    const CONTENT_LENGTH_DUMMY = 512;
    
    /**
     * @param string $binary
     * @return \ZipArchive
     */
    protected function generateArchive(string $binary = ''): \ZipArchive
    {
        $path = tempnam(sys_get_temp_dir(), 'php');
        if ($binary !== '') {
            file_put_contents($path, $binary);
        }
        register_shutdown_function(function () use ($path) {
            if (file_exists($path)) {
                unlink($path);
            }
        });
        $archive = new \ZipArchive();
        $archive->open($path);
        return $archive;
    }
    
    /**
     * キーを無視して配列が配列に含まれるか調べる。
     * @param array $subset
     * @param array $array
     */
    public function assertArraySubsetWithoutKey(array $subset, array $array)
    {
        foreach ($subset as $needle) {
            $this->assertContains($needle, $array);
        }
    }
    
    /**
     * @param string $inputFilename
     * @param string $inputFilePath
     * @param string $outputFilename
     * @param string $outputFilePath
     * @param string $contentType
     * @param string|null $from
     * @param string|null $to
     * @dataProvider dictionaryProvider
     */
    public function testConstruct(
        string $inputFilename,
        string $inputFilePath,
        string $outputFilename,
        string $outputFilePath,
        string $contentType,
        string $from = null,
        string $to = null
    ) {
        if ($from) {
            $_POST['from'] = $from;
        }
        if ($to) {
            $_POST['to'] = $to;
        }
        $inputFile = new \SplFileInfo(__DIR__ . "/resources/$inputFilePath");
        $_FILES['input'] = [
            'name' => $inputFilename,
            'type' => 'text/plain',
            'tmp_name' => $inputFile->getRealPath(),
            'error' => UPLOAD_ERR_OK,
            'size' => $inputFile->getSize(),
        ];
        $_SERVER['CONTENT_LENGTH'] = $inputFile->getSize();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        if ($contentType === 'application/zip') {
            ob_start();
            new Controller();
            if (http_response_code()) {
                $this->fail(ob_get_clean());
            }
            
            $archive = $this->generateArchive(ob_get_clean());
            $this->assertStringEqualsFile(
                __DIR__ . "/resources/$outputFilePath",
                $archive->getFromName('dictionary.csv')
            );
        } else {
            $this->expectOutputString(file_get_contents(__DIR__ . "/resources/$outputFilePath"));
            new Controller();
        }

        $this->assertSame(200, http_response_code() ?: 200);
        $this->assertArraySubsetWithoutKey([
            'access-control-allow-origin: *',
            "content-type: $contentType",
            'content-disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($outputFilename),
        ], xdebug_get_headers());
    }
    
    public function dictionaryProvider(): array
    {
        return [
            [
                '漢字.cfq',
                'catchfeeling/kanji-input.cfq',
                '漢字.csv',
                'catchfeeling/kanji-output.csv',
                'text/csv; charset=UTF-8; header=present',
            ],
            [
                '純粋な東方キャラ大辞典 ver.5 [語数 191] [作成者 幽燐, 100の人].cfq',
                'catchfeeling/touhou-input.cfq',
                '純粋な東方キャラ大辞典 ver．5.csv',
                'catchfeeling/touhou-output.csv',
                'text/csv; charset=UTF-8; header=present',
            ],
            [
                '純粋な東方キャラ大辞典 ver.5 [語数 191] [作成者 幽燐, 100の人].cfq',
                'catchfeeling/touhou-input.cfq',
                '純粋な東方キャラ大辞典 ver．5.csv',
                'catchfeeling/touhou-pictsense.csv',
                'text/csv; charset=UTF-8; header=absent',
                null,
                'ピクトセンス',
            ],
            [
                '英単語 [語数 26] [作成者 100の人].cfq',
                'catchfeeling/english-input.cfq',
                '英単語.csv',
                'catchfeeling/english-output.csv',
                'text/csv; charset=UTF-8; header=present',
            ],
            [
                '純粋な東方キャラ大辞典 ver.5 [語数 191] [作成者 幽燐, 100の人].dat',
                'catchm/touhou-input.dat',
                '純粋な東方キャラ大辞典 ver．5 [語数 191] [作成者 幽燐, 100の人].csv',
                'catchm/touhou-output.csv',
                'text/csv; charset=UTF-8; header=present',
            ],
            [
                '純粋な東方キャラ大辞典 ver.5 [語数 191] [作成者 幽燐, 100の人].dat',
                'catchm/touhou-input.dat',
                '純粋な東方キャラ大辞典 ver．5 [語数 191] [作成者 幽燐, 100の人] [語数 191].cfq',
                'catchm/touhou-catchfeeling.cfq',
                'text/plain; charset=Shift_JIS',
                null,
                'キャッチフィーリング',
            ],
            [
                'しりとりサンプル.txt',
                'inteligenceo/shiritori-input.txt',
                'しりとりサンプル.csv',
                'inteligenceo/shiritori-output.csv',
                'text/csv; charset=UTF-8; header=present',
            ],
            [
                'クイズサンプル.txt',
                'inteligenceo/quiz-input.txt',
                'クイズサンプル.csv',
                'inteligenceo/quiz-output.csv',
                'text/csv; charset=UTF-8; header=present',
            ],
            [
                'クイズサンプル.txt',
                'inteligenceo/quiz-input.txt',
                'クイズサンプル.txt',
                'inteligenceo/quiz-shiritori.txt',
                'text/plain; charset=Shift_JIS',
                null,
                'Inteligenceω しりとり',
            ],
            [
                'ファイル形式.csv',
                'generic-dictionary/formats-input.zip',
                'ファイル形式.zip',
                'generic-dictionary/formats-output-dictionary.csv',
                'application/zip',
            ],
            [
                '東方原曲 (紅魔郷〜紺珠伝) ※CD限定の曲は含まず.txt',
                'pictsense/touhou-musics-input.csv',
                '東方原曲 (紅魔郷〜紺珠伝) ※CD限定の曲は含まず.csv',
                'pictsense/touhou-musics-output.csv',
                'text/csv; charset=UTF-8; header=present',
                'ピクトセンス',
            ],
        ];
    }
    
    /**
     * @param string $type
     * @param string $title
     * @param int $status
     * @param bool $checkingLogicException
     */
    private function errorCommonTest(string $type, string $title, int $status, bool $checkingLogicException = false)
    {
        if (empty($_SERVER['CONTENT_LENGTH'])) {
            $_SERVER['CONTENT_LENGTH'] = 512;
        }
        $this->expectOutputRegex('#^{' . ($type === 'about:blank' ? '' : "\n    \"type\": \"$type\",") . "
    \"title\": \"$title\",
    \"status\": $status,
    \"detail\": \".+\"
}$#u");
        
        if ($checkingLogicException) {
            try {
                new Controller();
            } catch (\LogicException $e) {
            }
        } else {
            new Controller();
        }
        $this->assertSame($status, http_response_code() ?: 200);
        $this->assertArraySubsetWithoutKey([
            'access-control-allow-origin: *',
            'content-type: application/problem+json; charset=UTF-8',
        ], xdebug_get_headers());
    }
    
    /**
     * @param (string|int|(string|int)[])[][] $overwritingArray
     */
    protected function mergeToSuperglobals(array $overwritingArray)
    {
        foreach ($overwritingArray as $variableName => $array) {
            $GLOBALS[$variableName] += $array;
        }
    }
    
    /**
     * @param (string|int|(string|int)[])[][] $overwritingArray
     * @dataProvider badRequestProvider
     */
    public function testBadRequest(array $overwritingArray)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->mergeToSuperglobals($overwritingArray);
        $this->errorCommonTest('about:blank', 'Bad Request', 400);
    }
    
    public function badRequestProvider(): array
    {
        return [
            [[]],
            [[
                '_GET' => ['input' => "あ\r\nい\r\nう\r\nえ\r\nお"],
            ]],
            [[
                '_POST' => ['input' => "あ\r\nい\r\nう\r\nえ\r\nお"],
            ]],
            [[
                '_FILES' => ['input' => [
                    'name' => [
                        'test.txt',
                    ],
                    'type' => [
                        '',
                    ],
                    'tmp_name' => [
                        '',
                    ],
                    'error' => [
                         UPLOAD_ERR_OK,
                    ],
                    'size' => [
                        100,
                    ],
                ]],
            ]],
            [[
                '_FILES' => ['input' => [
                    'name' => [
                        'test.txt',
                    ],
                    'type' => [
                        '',
                    ],
                    'tmp_name' => [
                        '',
                    ],
                    'error' => [
                         UPLOAD_ERR_OK,
                    ],
                    'size' => [
                        100,
                    ],
                ]],
            ]],
        ];
    }
    
    /**
     * @param string $path
     * @param string $filename
     * @dataProvider malformedSyntaxProvider
     */
    public function testMalformedSyntax(string $path, string $filename)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $fileInfo = new \SplFileInfo(__DIR__ . "/$path");
        $_FILES['input'] = [
            'name' => $filename,
            'type' => '',
            'tmp_name' => $fileInfo->getRealPath(),
            'error' => UPLOAD_ERR_OK,
            'size' => $fileInfo->getSize(),
        ];
        $this->errorCommonTest(
            'https://github.com/esperecyan/dictionary-api/blob/master/malformed-syntax.md',
            'Malformed Syntax',
            400
        );
    }
    
    public function malformedSyntaxProvider(): array
    {
        return [
            [
                '../composer.json',
                'dictionary.zip',
            ],
            [
                '../phpunit.xml',
                'dictionary.xml',
            ],
            [
                'resources/dummy.zip',
                'dictionary.cfq',
            ],
        ];
    }
    
    /**
     * @param string $path
     * @param string $filename
     * @param string $to
     * @dataProvider serializeErrorProvider
     */
    public function testSerializeError(string $path, string $filename, string $to)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['to'] = $to;
        $fileInfo = new \SplFileInfo(__DIR__ . "/$path");
        $_FILES['input'] = [
            'name' => $filename,
            'type' => '',
            'tmp_name' => $fileInfo->getRealPath(),
            'error' => UPLOAD_ERR_OK,
            'size' => $fileInfo->getSize(),
        ];
        $this->errorCommonTest(
            'https://github.com/esperecyan/dictionary-api/blob/master/serialize-error.md',
            'Serialize Error',
            400
        );
    }
    
    public function serializeErrorProvider(): array
    {
        return [
            [
                'resources/catchfeeling/kanji-input.cfq',
                '漢字.cfq',
                'ピクトセンス',
            ],
            [
                'resources/inteligenceo/selections.txt',
                '記述問題が無い.txt',
                'キャッチフィーリング',
            ],
        ];
    }
    
    /**
     * @param string $method
     * @dataProvider methodNotAllowedProvider
     */
    public function testMethodNotAllowed(string $method)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $this->errorCommonTest('about:blank', 'Method Not Allowed', 405);
        $this->assertContains('allow: POST', xdebug_get_headers());
    }
    
    public function methodNotAllowedProvider(): array
    {
        return [
            ['GET'],
            ['HEAD'],
            ['PUT'],
            ['DELETE'],
        ];
    }
    
    /**
     * @param string $method
     * @dataProvider notImplementedProvider
     */
    public function testNotImplemented(string $method)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $this->errorCommonTest('about:blank', 'Not Implemented', 501);
    }
    
    public function notImplementedProvider(): array
    {
        return [
            ['CONNECT'],
            ['TRACE'],
            ['OPTIONS'],
            ['get'],
            ['post'],
        ];
    }
    
    /**
     * @param (string|int|(string|int)[])[][] $overwritingArray
     * @dataProvider payloadTooLargeProvider
     */
    public function testPayloadTooLarge(array $overwritingArray)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->mergeToSuperglobals($overwritingArray);
        $this->errorCommonTest('about:blank', 'Payload Too Large', 413);
    }
    
    public function payloadTooLargeProvider(): array
    {
        return [
            [[
                '_FILES' => ['input' => [
                    'name' => 'test.txt',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_INI_SIZE,
                    'size' => 0,
                ]],
            ]],
            [[
                '_FILES' => ['input' => [
                    'name' => 'test.txt',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_FORM_SIZE,
                    'size' => 0,
                ]],
            ]],
            [[
                '_FILES' => ['input' => [
                    'name' => 'test.txt',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_PARTIAL,
                    'size' => 0,
                ]],
            ]],
            [[
                '_FILES' => ['input' => [
                    'name' => 'test.txt',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_NO_FILE,
                    'size' => 0,
                ]],
            ]],
            [[
                // <http://okwave.jp/qa/q5163609.html>
                '_SERVER' => [
                    'CONTENT_LENGTH' => (new \bantu\IniGetWrapper\IniGetWrapper())->getBytes('post_max_size') + 1
                ],
            ]],
        ];
    }
    
    /**
     * @param (string|int|(string|int)[])[][] $overwritingArray
     * @dataProvider internalServerErrorProvider
     */
    public function testInternalServerError(array $overwritingArray)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->mergeToSuperglobals($overwritingArray);
        $this->errorCommonTest('about:blank', 'Internal Server Error', 500, true);
    }
    
    public function internalServerErrorProvider(): array
    {
        return [
            [[
                '_FILES' => ['input' => [
                    'name' => 'test.txt',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_NO_TMP_DIR,
                    'size' => 0,
                ]],
            ]],
            [[
                '_FILES' => ['input' => [
                    'name' => 'test.txt',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_CANT_WRITE,
                    'size' => 0,
                ]],
            ]],
            [[
                '_FILES' => ['input' => [
                    'name' => 'test.txt',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_EXTENSION,
                    'size' => 0,
                ]],
            ]],
            [[
                '_FILES' => ['input' => [
                    'name' => 'test.txt',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => 100,
                    'size' => 0,
                ]],
            ]],
        ];
    }
}
