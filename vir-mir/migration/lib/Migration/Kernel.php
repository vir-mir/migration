<?php
/**
 * Created by PhpStorm.
 * User: vir-mir
 * Date: 15.01.14
 * Time: 11:35
 */

namespace Migration;

use Migration\DataBase;

class Kernel {

    public function run() {
        DataBase::connectDB('wp', 'root', '', 'komtender', true);
        DataBase::connectDB('wp', 'root', '', 'komtender2', false);
    }


    public function getNewTables() {
        $dbTablesDebug = $this->detTablesDebug();
        $dbTablesReliz = $this->detTablesReliz();
        $dbTables = array_merge(array_diff($dbTablesDebug, $dbTablesReliz));
        $dbTablesRemove = array_merge(array_diff($dbTablesReliz, $dbTablesDebug));
        $table = array();
        if ($dbTables) $table['new'] = $dbTables;
        if ($dbTablesRemove) $table['remove'] = $dbTablesRemove;

        return $table;
    }

    public function addField($table, $field) {
        $ddl = $this->getFieldInfoName($table, $field, 'getDebug');
        $ddl = "ALTER TABLE `{$table}` ADD " . implode(' ', $ddl);
        return DataBase::getReliz()->query($ddl);
    }

    public function addIndex($table, $index) {
        $ddl = $this->getIndexInfoName($table, $index, 'getDebug');
        $ddl = "ALTER TABLE `{$table}` ADD {$ddl['name']} {$ddl['index']}";
        return DataBase::getReliz()->query($ddl);
    }

    public function changeIndex($table, $index) {
        $ddl = $this->removeIndex($table, $index);
        $ddl = $this->addIndex($table, $index);
    }

    public function removeIndex($table, $index) {
        $ddl = $this->getIndexInfoName($table, $index, 'getReliz');
        $ddl = "ALTER TABLE `{$table}` DROP {$ddl['name']}";
        return DataBase::getReliz()->query($ddl);
    }

    public function changeField($table, $field) {
        $ddl = $this->getFieldInfoName($table, $field, 'getDebug');
        $name = $ddl['name'];
        $ddl = "ALTER TABLE `{$table}` CHANGE {$name} " . implode(' ', $ddl);
        return DataBase::getReliz()->query($ddl);
    }

    public function removeField($table, $field) {
        $ddl = $this->getFieldInfoName($table, $field, 'getReliz');
        $name = $ddl['name'];
        $ddl = "ALTER TABLE `{$table}` DROP {$name}";
        return DataBase::getReliz()->query($ddl);
    }

    public function addTable($table) {
        $ddl = $this->getDDLTable($table, 'getDebug');
        return DataBase::getReliz()->query($ddl);
    }

    public function removeTable($table) {
        $ddl = $this->getDDLTable($table, 'getReliz');
        return DataBase::getDebug()->query($ddl);
    }

    public function getModifiedTable() {
        $dbTablesDebug = $this->detTablesDebug();
        $dbTablesReliz = $this->detTablesReliz();
        $dbTables = array_flip(array_unique(array_merge($dbTablesDebug, $dbTablesReliz)));
        $new = $this->getNewTables();
        $old = isset($new['remove'])?$new['remove']:array();
        $new = isset($new['new'])?$new['new']:array();
        $dbTables = array_merge(array_diff_key($dbTables, array_flip(array_merge($new, $old))));
        $this->comparison($dbTables);
        return $dbTables;
    }


    private function comparison(&$dbTables) {
        foreach($dbTables as $table=>&$v) {
            if ($v > -1) $v = array();

            $v['field'] = $this->comparisonFields($table);
            $v['index'] = $this->comparisonIndexs($table);
            if (empty($v['field']['new']) && empty($v['field']['remove']) && empty($v['field']['comparison'])) unset($v['field']);
            if (empty($v['index']['new']) && empty($v['index']['remove']) && empty($v['index']['comparison'])) unset($v['index']);
            if (empty($v)) unset($dbTables[$table]);
        }
    }

    private function comparisonFields($table) {
        $dbFieldsDebug = $this->getFieldInfo($table, 'getDebug');
        $dbFieldsReliz = $this->getFieldInfo($table, 'getReliz');

        $dbNewFields = array_merge(array_diff(array_keys($dbFieldsDebug), array_keys($dbFieldsReliz)));
        $dbRemoveFields = array_merge(array_diff(array_keys($dbFieldsReliz), array_keys($dbFieldsDebug)));
        $dbFieldsDebugComparison = array_merge(array_diff_key($dbFieldsDebug, array_flip($dbNewFields)));

        $return = array('new'=>array(), 'remove'=>array(), 'comparison'=>array(), );

        foreach ($dbNewFields as $fild) {
            $return['new'][$fild] = $dbFieldsDebug[$fild];
        }
        foreach ($dbRemoveFields as $fild) {
            $return['remove'][$fild] = $dbFieldsReliz[$fild];
        }

        foreach ($dbFieldsDebugComparison as $nameField=>$field) {
            $equally = true;
            foreach ($field as $key=>$val) {
                if ($dbFieldsReliz[$nameField][$key] != $val) {
                    $equally = false;
                    break;
                }
            }
            if (!$equally) {
                $equally = true;
                $return['comparison'][$nameField] = $dbFieldsDebug[$nameField];
            }
        }

        return $return;
    }

    private function comparisonIndexs($table) {
        $dbIndexsDebug = $this->getIndexInfo($table, 'getDebug');
        $dbIndexsReliz = $this->getIndexInfo($table, 'getReliz');

        $dbNewIndexs = array_merge(array_diff_key($dbIndexsDebug, $dbIndexsReliz));
        $dbRemoveIndexs = array_merge(array_diff_key($dbIndexsReliz, $dbIndexsDebug));
        $dbIndexsDebugComparison = array_merge(array_diff_key($dbIndexsDebug, $dbNewIndexs));
        $return = array('new'=>array(), 'remove'=>array(), 'comparison'=>array(), );

        foreach (array_keys($dbNewIndexs) as $index) {
            $return['new'][$index] = $dbIndexsDebug[$index];
        }
        foreach (array_keys($dbRemoveIndexs) as $index) {
            $return['remove'][$index] = $dbIndexsReliz[$index];
        }

        foreach ($dbIndexsDebugComparison as $nameIndex=>$index) {
            if ($dbIndexsReliz[$nameIndex]!= $index) {
                $return['comparison'][$nameIndex] = $dbIndexsDebug[$nameField];
            }
        }

        return $return;
    }

    private function getFieldInfo($table, $db) {
        $sql = "SHOW COLUMNS FROM {$table}";
        $res = DataBase::$db()->query($sql);
        if ($res && $res->rowCount() > 0) {
            while($row = $res->fetchObject()) {
                $return[$row->Field] = array(
                    'type' => $row->Type,
                    'null' => mb_strtolower($row->Null)=='yes'?'NULL':'NOT NULL',
                    'default' => $row->Default?"DEFAULT '{$row->Default}'":'',
                    'extra' => $row->Extra,
                );
            }
            return $return;
        }
        return array();
    }

    private function getIndexInfo($table, $db) {
        $sql = "SHOW INDEX FROM {$table}";
        $res = DataBase::$db()->query($sql);
        if ($res && $res->rowCount() > 0) {
            while($row = $res->fetchObject()) {

                if (mb_strtolower($row->Key_name) == 'primary') {
                    $name = 'PRIMARY KEY';
                } else {
                    if ($row->Non_unique == 0) {
                        $name = "UNIQUE KEY `{$row->Key_name}`";
                    } else {
                        $name = "KEY `{$row->Key_name}`";
                    }
                }

                $index = "`{$row->Column_name}`";

                $indexList = array();
                if (isset($return[$name])) {
                    $indexList = $return[$name];
                    $indexList = trim($indexList, ' ()');
                    $indexList = explode(',', $indexList);
                }

                if ($row->Sub_part > 0) {
                    $index .= "($row->Sub_part)";
                }

                array_push($indexList, $index);
                $return[$name] = "(".implode(', ', $indexList).")";
            }

            return $return;
        }
        return array();
    }

    private function getIndexInfoName($table, $index, $db) {
        if (preg_match('~`(.*)`~isU', $index, $indexAr)) {
            $index = $indexAr[1];
        } else {
            $index = explode(' ', $index);
            $index = array_shift($index);
        }

        $sql = "SHOW INDEX FROM {$table} where Key_name = '{$index}'";
        $res = DataBase::$db()->query($sql);
        if ($res && $res->rowCount() > 0) {
            while($row = $res->fetchObject()) {
                if (mb_strtolower($row->Key_name) == 'primary') {
                    $name = 'PRIMARY KEY';
                } else {
                    if ($row->Non_unique == 0) {
                        $name = "UNIQUE KEY `{$row->Key_name}`";
                    } else {
                        $name = "KEY `{$row->Key_name}`";
                    }
                }

                $index = "`{$row->Column_name}`";

                $indexList = array();
                if (isset($return[$name])) {
                    $indexList = $return[$name];
                    $indexList = trim($indexList, ' ()');
                    $indexList = explode(',', $indexList);
                }

                if ($row->Sub_part > 0) {
                    $index .= "($row->Sub_part)";
                }

                array_push($indexList, $index);
                $return[$name] = "(".implode(', ', $indexList).")";
            }

            return array('name'=>$name, 'index'=>$return[$name]);
        }
        return array();
    }


    private function getFieldInfoName($table, $field, $db) {
        $sql = "SHOW COLUMNS FROM {$table} WHERE Field = '{$field}'";
        $res = DataBase::$db()->query($sql);
        if ($res && $res->rowCount() > 0) {
            while($row = $res->fetchObject()) {
                $return = array(
                    'name' => "`{$row->Field}`",
                    'type' => $row->Type,
                    'null' => mb_strtolower($row->Null)=='yes'?'NULL':'NOT NULL',
                    'default' => $row->Default?"DEFAULT '{$row->Default}'":'',
                    'extra' => $row->Extra,
                );
            }
            return $return;
        }
        return array();
    }


    private function detTablesDebug() {
        return $this->getTables('getDebug');
    }

    private function detTablesReliz() {
        return $this->getTables('getReliz');
    }

    private function getDDLTable($table, $db) {
        $sql = "SHOW CREATE TABLE {$table}";
        $res = DataBase::$db()->query($sql);
        if ($res && $res->rowCount() > 0) {
            return $res->fetchColumn(1);
        }
        return null;
    }

    private function getTables($db){
        $sql = 'show tables';
        $res = DataBase::$db()->query($sql);
        if ($res && $res->rowCount() > 0) {
            $row = array();
            while ($row[] = $res->fetchColumn());
            array_pop($row);
            return $row;
        }
        return array();
    }

} 