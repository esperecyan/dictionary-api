<?php
namespace esperecyan\dictionary_api\xmlhttprequest;

class FormData
{
    /** @var string[] */
    protected $bodies = [];
    
    /** @var string */
    protected $boundaryString;
    
    /**
     * 本体部分のヘッダ向けに改行とnull文字を処理します。
     * @param string $str
     * @return string
     */
    protected function replaceLineFeed(string $str): string
    {
        return str_replace(["\r\n", "\r", "\n"], ' ', explode("\0", $str, 2)[0]);
    }
    
    /**
     * 本体部分のヘッダのパラメータ値向けに引用符で囲みます。
     * @param string $str
     * @return string
     */
    protected function quote(string $str): string
    {
        return '"' . addcslashes($this->replaceLineFeed($str), '"\\') . '"';
    }
    
    /**
     * エントリを追加します。
     * @param string $name
     * @param string $value
     * @param string|null $filename
     * @param string|null $type
     */
    public function append(string $name, string $value, string $filename = null, string $type = null)
    {
        $body = 'content-disposition: form-data; name=' . $this->quote($name);
        if (isset($filename)) {
            $body .= '; filename=' . $this->quote($filename);
        }
        $body .= "\r\n";
        
        if (isset($type)) {
            $body .= 'content-type: ' . $this->replaceLineFeed($type) . "\r\n";
        }
        
        $body .= "\r\n$value";
        
        $this->bodies[] = $body;
    }
    
    /**
     * multipart/form-data境界文字列を取得します。
     * @return string
     */
    protected function getBoundaryString(): string
    {
        if (!$this->boundaryString) {
            $bodies = implode(' ', $this->bodies);
            do {
                $this->boundaryString = '----------------------------------------' . random_int(0, PHP_INT_MAX);
            } while (strpos($bodies, $this->boundaryString) !== false);
        }
        return $this->boundaryString;
    }
    
    /**
     * multipart/form-data境界文字列を含むcontent-typeヘッダを返します。
     * @return string
     */
    public function getContentType(): string
    {
        return 'content-type: multipart/form-data; boundary=' . $this->getBoundaryString();
    }
    
    /**
     * multipart/form-data形式で返します。
     * @return string
     */
    public function encode(): string
    {
        $boundaryString = $this->getBoundaryString();
        return "--$boundaryString\r\n"
            . implode("\r\n--$boundaryString\r\n", $this->bodies) . "\r\n--$boundaryString--\r\n";
    }
}
