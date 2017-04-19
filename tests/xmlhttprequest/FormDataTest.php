<?php
namespace esperecyan\dictionary_api\xmlhttprequest;

class FormDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string[][] $formDataSet
     * @param (string[]|string)[][] $expectResult
     * @dataProvider dictionaryProvider
     */
    public function testEncode(array $formDataSet, array $expectResult)
    {
        $formData = new FormData();
        foreach ($formDataSet as $entry) {
            $formData->append($entry['name'], $entry['value'], $entry['filename'] ?? null, $entry['type'] ?? null);
        }
        
        $this->assertEquals(
            $expectResult,
            (new \h4cc\Multipart\ParserSelector())
                ->getParserForContentType(str_replace('content-type: ', '', $formData->getContentType()))
                ->parse($formData->encode())
        );
    }
    
    public function dictionaryProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => '',
                        'value' => '',
                    ],
                    [
                        'name' => "\\\"\n\r\n\r𩸽\0テスト",
                        'value' => "/\0/\r/\n/", // h4cc/multipart は、本体部分の内容にバイト列 "\r\n" が含まれていると正常にデコードできない
                        'filename' => "\\\"\n\r\n\r𩸽\0テスト",
                        'type' => "text/p\r\nl\0ain",
                    ],
                ],
                [
                    [
                        'headers' => [
                            'content-disposition' => ['form-data; name=""'],
                            'content-type' => ['text/plain'],
                        ],
                        'body' => '',
                    ],
                    [
                        'headers' => [
                            'content-disposition' => ['form-data; name="\\\\\\"   𩸽"; filename="\\\\\\"   𩸽"'],
                            'content-type' => ['text/p l'],
                        ],
                        'body' => "/\0/\r/\n/",
                    ],
                ],
            ],
        ];
    }
}
