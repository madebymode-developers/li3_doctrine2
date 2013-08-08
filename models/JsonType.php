<?php
namespace li3_doctrine2\models\types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class JsonType extends Type {

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
        return $platform->getDoctrineTypeMapping('STRING');
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        // This is executed when the value is read from the database. Make your conversions here, optionally using the $platform.
        return json_decode($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value != null) {
            // This is executed when the value is written to the database. Make your conversions here, optionally using the $platform.
            return json_encode($value);
        }
    }

    public function getName()
    {
        return 'json';
    }

}
