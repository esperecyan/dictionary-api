<?php
namespace esperecyan\dictionary_api;

use esperecyan\dictionary_php\{Parser, Serializer};
use esperecyan\dictionary_php\exception\{SyntaxException, SerializeExceptionInterface};
use bantu\IniGetWrapper\IniGetWrapper;

class Controller
{
    /** @var string */
    const ERROR_SCHEMA_URL = 'https://raw.githubusercontent.com/esperecyan/dictionary-api/master/error-schema.json';
    
    public function __construct()
    {
        header('access-control-allow-origin: *');
        if ($this->checkMethod($_SERVER['REQUEST_METHOD']) && ($file = $this->getInputFile())) {
            $parser = new Parser($this->getPostValue('from'), $_FILES['input']['name'], $this->getPostValue('title'));
            try {
                $dictionary = $parser->parse($file);
                $outputFile = (new Serializer())->serialize($dictionary);
                header("content-type: $outputFile[type]");
                header('content-disposition: attachment; filename*=utf-8\'\'' . rawurlencode($outputFile['name']));
                echo $outputFile['bytes'];
            } catch (SyntaxException $e) {
                $this->responseError(400, 'MalformedSyntax', $e->getMessage());
            } catch (SerializeExceptionInterface $e) {
                $this->responseError(400, 'MalformedSyntax', $e->getMessage());
            } catch (\Throwable $e) {
                $this->responseError(500, 'InternalServerError', _('ファイルの変換に失敗しました。'));
                throw $e;
            }
        }
    }
    
    /**
     * 入力が確実にutf-8以外になる設定であれば真を返します。
     * @return bool
     */
    protected function inputIsNotUTF8(): bool
    {
        return ini_get('mbstring.encoding_translation') === '1' && mb_strtolower(mb_internal_encoding()) !== 'utf-8';
    }
    
    /**
     * 入力が確実にutf-8になる設定であれば真を返します。
     * @return bool
     */
    protected function inputIsUTF8(): bool
    {
        return ini_get('mbstring.encoding_translation') === '1' && mb_strtolower(mb_internal_encoding()) === 'utf-8';
    }
    
    /**
     * 指定されたキーの値がPOSTされており、かつ空文字列でない場合にその値を返します。
     * @param string $key
     * @return string|null
     */
    protected function getPostValue(string $key)
    {
        $convertedKey = $this->inputIsNotUTF8() ? mb_convert_encoding($key, mb_internal_encoding(), 'utf-8') : $key;
        if (isset($_POST[$convertedKey]) && is_string($_POST[$convertedKey])) {
            $value = $this->inputIsUTF8() ? $_POST[$key] : mb_convert_encoding($_POST[$key], 'utf-8');
            if ($value !== '') {
                return $value;
            }
        }
    }
    
    /**
     * 要求メソッドがPOSTであるかチェックし、それ以外の場合はエラーメッセージの出力などを行います。
     * @param string $method
     * @return bool メソッドが正しい場合に真。
     */
    protected function checkMethod($method): bool
    {
        $valid = false;
        switch ($method) {
            case 'POST':
                $valid = true;
                break;
            case 'GET':
            case 'HEAD':
            case 'PUT':
            case 'DELETE':
                header('allow: POST');
                $this->responseError(405, 'MethodNotAllowed', sprintf(_('%sメソッドは利用できません。POSTメソッドを使用してください。'), $method));
                break;
            default:
                $this->responseError(501, 'NotImplemented', sprintf(_('%sメソッドは利用できません。POSTメソッドを使用してください。'), $method));
        }
        return $valid;
    }
    
    /**
     * アップロードできるファイルの最大バイト数を取得します。
     * @return int
     */
    protected function getMaxFileBytes(): int
    {
        $iniGetWrapper = new IniGetWrapper();
        return min(
            $iniGetWrapper->getBytes('upload_max_filesize'),
            $iniGetWrapper->getBytes('post_max_size'),
            $iniGetWrapper->getBytes('memory_limit')
        );
    }
    
    /**
     * アップロードされたファイルを取得します。失敗した場合はエラーメッセージの出力などを行います。
     * @return \SplFileInfo|null
     */
    protected function getInputFile()
    {
        $bytesErrorMessage = _('ファイルのアップロードに失敗しました。') . sprintf(
            _('ファイルサイズは %s を超えないようにしてください。'),
            (new \ScriptFUSION\Byte\ByteFormatter())->format($this->getMaxFileBytes())
        );
        
        $key = $this->inputIsNotUTF8() ? mb_convert_encoding('input', mb_internal_encoding(), 'utf-8') : 'input';
        if ($_SERVER['CONTENT_LENGTH'] > (new IniGetWrapper())->getBytes('post_max_size')) {
            $this->responseError(413, 'PayloadTooLarge', $bytesErrorMessage);
        } elseif (!isset($_FILES[$key]['error']) || !is_int($_FILES[$key]['error'])) {
            $this->responseError(400, 'MalformedRequest', _('inputキーで辞書ファイルを送信してください。'));
        } elseif ($_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
            if (in_array(
                $_FILES[$key]['error'],
                [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE]
            )) {
                $this->responseError(413, 'PayloadTooLarge', $bytesErrorMessage);
            } else {
                $this->responseError(500, 'InternalServerError', _('ファイルのアップロードに失敗しました。'));
                throw new \LogicException('ファイルのアップロードに関するエラーが発生しました。エラーコード: '. $_FILES[$key]['error']);
            }
        } else {
            return new \SplFileInfo($_FILES[$key]['tmp_name']);
        }
    }
    
    /**
     * HTTPステータスコードを設定し、エラーメッセージをJSONで出力します。
     * @param int $httpStatusCode
     * @param string $code
     * @param string $message
     */
    protected function responseError(int $httpStatusCode, string $code, string $message)
    {
        header(
            'content-type: application/json; charset=utf-8; profile=' . self::ERROR_SCHEMA_URL,
            true,
            $httpStatusCode
        );
        
        echo json_encode([
            [
                'code' => $code,
                'messages' => [$message],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
