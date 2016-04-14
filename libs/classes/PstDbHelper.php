<?php
/**
 * Created by Produweb
 * User: Simon Bonjean
 * Date: 4/10/15
 * Time: 14:58
 */

class PstDbHelper extends Helper{
    public static function importFixtures($model, $datas)
    {
        foreach($datas as $data){
            $record = new $model();
            foreach($data as $key => $value)
            {
                $record->{$key} = $value;
            }
            $record->save();
        }
    }
}