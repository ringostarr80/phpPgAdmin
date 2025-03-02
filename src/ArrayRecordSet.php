<?php

declare(strict_types=1);

namespace PhpPgAdmin;

/**
 * Really simple RecordSet to allow printTable of arrays.
 *
 * $Id: ArrayRecordSet.php,v 1.3 2007/01/10 01:46:28 soranzo Exp $
 */
class ArrayRecordSet
{
    /**
     * @var array<mixed>
     */
    private array $array;
    public bool $EOF = false;
    public mixed $fields;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->array = $data;
        $this->fields = reset($this->array);
        if ($this->fields === false) {
            $this->EOF = true;
        }
    }

    public function recordCount(): int
    {
        return count($this->array);
    }

    public function moveNext(): void
    {
        $this->fields = next($this->array);
        if ($this->fields === false) {
            $this->EOF = true;
        }
    }
}
