<?php

namespace App\Core\Models\EZT2;
use App\Core\Models\BaseModel;

class PhoneType extends BaseModel
{
    protected $connection = 'ezt2';

    protected $primaryKey = 'phone_type';
    
    protected $table = 'phone_type';

    /**
     * @return mixed
     */
    public static function getValidTypes()
    {
        return self::pluck('phone_type', 'phone_type')->toArray();
    }

    /**
     * @param string $defaultValue
     * @return mixed
     */
    public static function getTypeList($defaultValue = 'Main Phone Label')
    {
        $list = self::pluck('phone_label', 'phone_type');
        $list->prepend($defaultValue, '');

        return $list;
    }

    /**
     * Match an existing order_core.phone.type to an ezt2.phone_type.phone_label
     *
     * @param $typeName
     * @return int
     */
    public static function getType($typeName)
    {
        $matchedType = self::where('phone_label', 'like', $typeName)->first();
        if (null === $matchedType) {
            //Default to phone
            return 0;
        }
        return $matchedType->phone_type;
    }
}