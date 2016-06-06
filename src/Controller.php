<?php
namespace esperecyan\dictionary_api;

use esperecyan\dictionary_php\{Parser, Serializer};
use esperecyan\dictionary_php\exception\{SyntaxException, SerializeExceptionInterface};
use bantu\IniGetWrapper\IniGetWrapper;

class Controller
{
    public function __construct()
    {
        header('access-control-allow-origin: *');
        if ($this->checkMethod($_SERVER['REQUEST_METHOD']) && ($file = $this->getInputFile())) {
            $parser = new Parser($this->getPostValue('from'), $_FILES['input']['name'], $this->getPostValue('title'));
            try {
                $dictionary = $parser->parse($file);
                $outputFile = (new Serializer($this->getPostValue('to') ?? '汎用辞書'))->serialize($dictionary);
                header("content-type: $outputFile[type]");
                header('content-disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($outputFile['name']));
                echo $outputFile['bytes'];
            } catch (SyntaxException $e) {
                $this->responseError([
                    'type' => 'https://github.com/esperecyan/dictionary-api/blob/master/malformed-syntax.md',
                    'title' => 'Malformed Syntax',
                    'status' => 400,
                    'detail' => $e->getMessage(),
                ]);
            } catch (SerializeExceptionInterface $e) {
                $this->responseError([
                    'type' => 'https://github.com/esperecyan/dictionary-api/blob/master/serialize-error.md',
                    'title' => 'Serialize Error',
                    'status' => 400,
                    'detail' => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                $this->responseError([
                    'title' => 'Internal Server Error',
                    'status' => 500,
                    'detail' => _('ファイルの変換に失敗しました。'),
                ]);
                throw $e;
            }
        }
    }
    
    /**
     * 入力が確実にUTF-8以外になる設定であれば真を返します。
     * @return bool
     */
    protected function inputIsNotUTF8(): bool
    {
        return ini_get('mbstring.encoding_translation') === '1' && mb_internal_encoding() !== 'UTF-8';
    }
    
    /**
     * 入力が確実にUTF-8になる設定であれば真を返します。
     * @return bool
     */
    protected function inputIsUTF8(): bool
    {
        return ini_get('mbstring.encoding_translation') === '1' && mb_internal_encoding() === 'UTF-8';
    }
    
    /**
     * 指定されたキーの値がPOSTされており、かつ空文字列でない場合にその値を返します。
     * @param string $key
     * @return string|null
     */
    protected function getPostValue(string $key)
    {
        $convertedKey = $this->inputIsNotUTF8() ? mb_convert_encoding($key, mb_internal_encoding(), 'UTF-8') : $key;
        if (isset($_POST[$convertedKey]) && is_string($_POST[$convertedKey])) {
            $value = $this->inputIsUTF8() ? $_POST[$key] : mb_convert_encoding($_POST[$key], 'UTF-8');
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
                $this->responseError([
                    'title' => 'Method Not Allowed',
                    'status' => 405,
                    'detail' => sprintf(_('%sメソッドは利用できません。POSTメソッドを使用してください。'), $method),
                ]);
                break;
            default:
                $this->responseError([
                    'title' => 'Not Implemented',
                    'status' => 501,
                    'detail' => sprintf(_('%sメソッドは利用できません。POSTメソッドを使用してください。'), $method),
                ]);
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
        
        $key = $this->inputIsNotUTF8() ? mb_convert_encoding('input', mb_internal_encoding(), 'UTF-8') : 'input';
        if ($_SERVER['CONTENT_LENGTH'] > (new IniGetWrapper())->getBytes('post_max_size')) {
            $this->responseError([
                'title' => 'Payload Too Large',
                'status' => 413,
                'detail' => $bytesErrorMessage,
            ]);
        } elseif (!isset($_FILES[$key]['error']) || !is_int($_FILES[$key]['error'])) {
            $this->responseError([
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => _('inputキーで辞書ファイルを送信してください。'),
            ]);
        } elseif ($_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
            if (in_array(
                $_FILES[$key]['error'],
                [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE]
            )) {
                $this->responseError([
                    'title' => 'Payload Too Large',
                    'status' => 413,
                    'detail' => $bytesErrorMessage,
                ]);
            } else {
                $this->responseError([
                    'title' => 'Internal Server Error',
                    'status' => 500,
                    'detail' => _('ファイルのアップロードに失敗しました。'),
                ]);
                throw new \LogicException('ファイルのアップロードに関するエラーが発生しました。エラーコード: '. $_FILES[$key]['error']);
            }
        } else {
            return new \SplFileInfo($_FILES[$key]['tmp_name']);
        }
    }
    
    /**
     * HTTPステータスコードを設定し、エラーメッセージをJSONで出力します。
     * @param (string|int)[] $problemdetail
     */
    protected function responseError(array $problemdetail)
    {
        header('content-type: application/problem+json; charset=UTF-8', true, $problemdetail['status']);
        echo json_encode($problemdetail, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
