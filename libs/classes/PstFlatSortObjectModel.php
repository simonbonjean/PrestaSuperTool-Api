<?php
/**
 * Created by Produweb
 * User: Simon Bonjean
 * Date: 24/09/13
 * Time: 11:39
 */

class PstFlatSortObjectModelCore extends ObjectModel{
    /** @var integer position */
    public $position;

    /**
     * Moves a obj
     *
     * @since 1.5.0
     * @param boolean $way Up (1) or Down (0)
     * @param integer $position
     * @return boolean Update result
     */
    public function updatePosition($way, $position)
    {
        $primary = $this->def['primary'];
        $table = $this->def['table'];
        $soft_delete = @$this->soft_delete?'WHERE `deleted` = 0':'';
        if (!$res = Db::getInstance()->executeS('
			SELECT `'.$primary.'`, `position`
			FROM `'._DB_PREFIX_.$table.'`
			'.$soft_delete.'
			ORDER BY `position` ASC'
        ))
            return false;

        foreach ($res as $obj)
            if ((int)$obj[$primary] == (int)$this->id)
                $moved_obj = $obj;

        if (!isset($moved_obj) || !isset($position))
            return false;

        $soft_delete = @$this->soft_delete?'AND `deleted` = 0':'';
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        return (Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.$table.'`
			SET `position`= `position` '.($way ? '- 1' : '+ 1').'
			WHERE `position`
			'.($way
                ? '> '.(int)$moved_obj['position'].' AND `position` <= '.(int)$position
                : '< '.(int)$moved_obj['position'].' AND `position` >= '.(int)$position.'
			'.$soft_delete))
            && Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.$table.'`
			SET `position` = '.(int)$position.'
			WHERE `'.$primary.'` = '.(int)$moved_obj[$primary]));
    }

    /**
     * Reorders obj positions.
     * Called after deleting a obj.
     *
     * @since 1.5.0
     * @return bool $return
     */
    public function cleanPositions()
    {
        $primary = $this->def['primary'];
        $table = $this->def['table'];

        $return = true;

        $soft_delete = @self::$definition['soft_delete']?'WHERE `deleted` = 0':'';

        $sql = '
		SELECT `'.$primary.'`
		FROM `'._DB_PREFIX_.$table.'`
		'.$soft_delete.'
		ORDER BY `position` ASC';
        $result = Db::getInstance()->executeS($sql);

        $i = 0;
        foreach ($result as $value)
            $return = Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.$table.'`
			SET `position` = '.(int)$i++.'
			WHERE `'.$primary.'` = '.(int)$value[$primary]);
        return $return;
    }

    /**
     * Gets the highest carrier position
     *
     * @since 1.5.0
     * @return int $position
     */
    public function getHigherPosition()
    {
        $table = $this->def['table'];
        $sql = 'SELECT MAX(`position`)
				FROM `'._DB_PREFIX_.$table.'`';
        $position = DB::getInstance()->getValue($sql);
        return (is_numeric($position)) ? $position : -1;
    }

    public function add($autodate = true, $null_values = false)
    {

        if ($this->position <= 0)
        {
            $this->position = $this->getHigherPosition() + 1;
        }

        return parent::add($autodate, $null_values);
    }
    public function delete()
    {
        if (!parent::delete())
            return false;
        $this->cleanPositions();
    }
}