<?php
declare(strict_types=1);

namespace Peraleks\ErrorHandler\Notifiers;


use Peraleks\ErrorHandler\Exception\PropertyMustBeDefinedException;
use Peraleks\ErrorHandler\Exception\PropertyTypeException;

class TailNotifier extends CliNotifier
{
    const REPEAT = "\033[31m%s\033[0";
    const DATE   = "\033[33m%s\033[0";

    protected function prepare()
    {
        if (!$file = $this->settingsObject->get('file')) {
            throw new PropertyMustBeDefinedException('file');
        }
        if (!is_string($file)) {
            throw new PropertyTypeException($file, 'file', 'string');
        }
        parent::prepare();
    }


    public function notify()
    {
        $notice =& $this->preparedNotice;

        $file = $this->settingsObject->get('file');
        $fileRepeat = $file.'.repeat';

        if (!file_exists($fileRepeat)) file_put_contents($fileRepeat, '');
        $fileRepeatRes = fopen($fileRepeat, 'rb');
        if (!$fileRepeatRes) return;

        $a = crc32($notice);
        $b = (int)fread($fileRepeatRes, 12);
        if ($a == $b) {
            $notice = $this->time().sprintf(static::REPEAT, '>>repeat ');
        } else {
            $fileRepeatRes = fopen($fileRepeat, 'wb');
            if (!$fileRepeatRes) return;
            fwrite($fileRepeatRes, (string)crc32($notice));
            $notice = "\n\n".$this->time().' '.$notice;
        }
        fclose($fileRepeatRes);

        $fileRes = fopen($file, 'ab');
        if (!$fileRes) return;
        fwrite($fileRes, $notice);
        fclose($fileRes);
    }

    protected function time(): string
    {
        return sprintf(static::DATE, date('H:i:s'));
    }

}