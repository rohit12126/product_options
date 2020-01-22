<?php

/**
 * An address file represents a collection of addresses that an invoice_item will be sent to.
 * Address files can be either purchased through the website or user's can upload their own.
 */

namespace App\Core\Models\OrderCore;
use App\Core\Models\BaseModel;
use App\Core\Models\OrderCore\AddressFile\Data;
use PhpOffice\PhpSpreadsheet;
use Carbon\Carbon;

class AddressFile extends BaseModel
{
    const UPDATED_AT = null; // work-around

    /**
     * Specify the DB connection to use.
     *
     * @string
     */
    protected $connection = 'order_core';

    /**
     * Specify the table to use.
     *
     * @var string
     */
    protected $table = 'address_file';

    protected $guarded = [];

    /**
     * @param $value
     */
    public function setUpdatedAt($value)
    {
        ;
    }

    /**
     * @param null $name
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getData($name = null)
    {
        return $this->hasMany(Data::class, 'address_file_id', 'id')->where('name', '=', $name);
    }

    /**
     * @return mixed
     */
    public function dataProduct()
    {
        return $this->hasOne(DataProduct::class, 'id', 'data_product_id');
    }

    /**
     * Determine if a user should be charged for an address list.
     *
     * @return bool
     */
    public function needsCharge()
    {
        if ($this->getData('listOrderId')->first() || $this->paid) {
            $expiredDate = new Carbon((is_null($this->date_paid) ? date('Y-m-d') : $this->date_paid));
            $now = Carbon::now();
            if ($expiredDate->diffInYears($now) < 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * Copy an address file.
     *
     * @return AddressFile
     */
    public function cloneToNew()
    {
        $addressFile = new AddressFile();
        $addressFile->user_id = $this->user_id;
        $addressFile->type = $this->type;
        $addressFile->name = $this->name;
        $addressFile->file = $this->file;
        $addressFile->count = $this->count;
        $addressFile->token = $this->token;
        $addressFile->data_product_id = $this->data_product_id;
        $addressFile->legacy_file_id = $this->legacy_file_id;
        $addressFile->save();

        $addressFile->setDataValue('originalAddressFileId', $this->id);

        foreach ($this->getData() as $data) {
            switch ($data->name) {
                case 'list':
                case 'listProvider':
                    $addressFile->setDataValue($data->name, $data->value);
                    break;
                case 'intendedCount':
                    $addressFile->count = $data->value;
                    $addressFile->save();
                    break;
            }
        }

        $this->is_active = false;
        $this->save();

        return $addressFile;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setDataValue($name, $value)
    {
        if (is_null($data = $this->getData($name)->first())) {
            $data = new Data();
            $data->name = $name;
        }
        $data->value = $value;
        $data->address_file_id = $this->id;
        $data->save();
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopePurchased($query)
    {
        return $query->whereNotNull('data_product_id');
    }

    /**
     * Parse file and return non empty row count
     *
     * @return int
     * @throws PhpSpreadsheet\Exception
     * @throws PhpSpreadsheet\Reader\Exception
     */
    public function parseCount()
    {
        $inputFileName = config('app.server_config.user_print_file_root') . '/' . $this->path . '/' . $this->file;
        $inputFileType = PhpSpreadsheet\IOFactory::identify($inputFileName);
        $reader = PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($inputFileName);
        $sheet = $spreadsheet->getActiveSheet();
        $rowCount = 0;

        // get count non empty count
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                if (!empty($cell->getValue())) {
                    $rowCount++;
                    break;
                }
            }
        }

        return $rowCount;
    }
}