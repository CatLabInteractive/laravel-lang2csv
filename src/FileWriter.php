<?php

namespace CatLab\Lang2Csv;

/**
 * Class FileWriter
 * @package CatLab\Lang2Csv
 */
class FileWriter
{
    /**
     * @var string
     */
    private $filename;

    const NL = "\n";
    const TAB = "\t";

    /**
     * FileWriter constructor.
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->content = "";

        $this->writeln("<?php");
        $this->writeln("return [");
    }

    /**
     * @param array $data
     */
    public function write(array $data)
    {
        $this->writeArray($data, 1);
    }


    protected function writeArray(array $array, $depth)
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $this->writeln($this->toKey($k), $depth);
                $this->writeln("[", $depth);
                $this->writeArray($v, $depth + 1);
                $this->writeln(']', $depth);
            } else {
                $this->writeValue($k, $v, $depth);
            }
        }
    }

    /**
     * @param string $k
     * @param string $v
     * @param int $depth
     */
    protected function writeValue($k, $v, $depth)
    {
        $this->writeln($this->toKey($k) . $this->toValue($v) . ",", $depth);
    }

    /**
     * @param $name
     * @return string
     */
    protected function toKey($name)
    {
        return '"' . $this->escape($name) . '" => ';
    }

    protected function toValue($name)
    {
        return '"' . $this->escape($name) . '"';
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function escape($value)
    {
        return str_replace('"', '\"', $value);
    }

    /**
     * Actually write the content and save to file.
     */
    public function save()
    {
        $this->writeln("];");
        file_put_contents($this->filename, $this->content);
    }

    /**
     * Write a line
     * @param $content
     * @param int $tabs
     */
    protected function writeln($content, $tabs = 0)
    {
        $this->content .= str_repeat(self::TAB, $tabs) . $content . self::NL;
    }
}