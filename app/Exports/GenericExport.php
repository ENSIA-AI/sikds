<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GenericExport implements FromCollection, FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    protected $data;

    protected array $headings;

    protected $mapMethod;

    /**
     * @param  Builder|Collection|array  $data
     */
    public function __construct($data, array $headings, callable $mapMethod)
    {
        $this->data = $data;
        $this->headings = $headings;
        $this->mapMethod = $mapMethod;
    }

    /**
     * @return Builder|Relation|QueryBuilder|null
     */
    public function query()
    {
        if ($this->data instanceof Builder || $this->data instanceof Relation || $this->data instanceof QueryBuilder) {
            return $this->data;
        }

        return null;
    }

    public function collection()
    {
        if ($this->data instanceof Collection) {
            return $this->data;
        }

        if (is_array($this->data)) {
            return collect($this->data);
        }

        if ($this->data !== null) {
            return collect($this->data);
        }

        return collect();
    }

    public function map($row): array
    {
        return call_user_func($this->mapMethod, $row);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
