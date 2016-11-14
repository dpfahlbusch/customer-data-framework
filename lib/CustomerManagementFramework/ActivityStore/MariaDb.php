<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 10.10.2016
 * Time: 11:22
 */

namespace CustomerManagementFramework\ActivityStore;

use CustomerManagementFramework\ActivityList\DefaultMariaDbActivityList;
use CustomerManagementFramework\ActivityStoreEntry\DefaultActivityStoreEntry;
use CustomerManagementFramework\ActivityStoreEntry\ActivityStoreEntryInterface;
use CustomerManagementFramework\Filter\ExportActivitiesFilterParams;
use CustomerManagementFramework\Model\ActivityInterface;
use CustomerManagementFramework\Model\CustomerInterface;
use Import\Customer;
use Pimcore\Db;
use Pimcore\Model\Object\Concrete;

class MariaDb implements ActivityStoreInterface{


    const ACTIVITIES_TABLE = 'plugin_cmf_activities2';
    const DELETIONS_TABLE = 'plugin_cmf_deletions';

    public function insertActivityIntoStore(ActivityInterface $activity) {

        $data = self::createDbRowData($activity);
        $data['creationDate'] = time();

        \CustomerManagementFramework\Service\MariaDb::getInstance()->insert(self::ACTIVITIES_TABLE, $data);
    }

    public function updateActivityInStore(ActivityInterface $activity, ActivityStoreEntryInterface $entry) {

        $data = self::createDbRowData($activity);

        \CustomerManagementFramework\Service\MariaDb::getInstance()->update(self::ACTIVITIES_TABLE, $data, "id = " . $entry->getId());
    }

    public function getEntryForActivity(ActivityInterface $activity)
    {
        $db = Db::get();

        $row = false;
        if($activity instanceof Concrete) {
            $row = $db->fetchRow("select *, column_json(attributes) as attributes from " . self::ACTIVITIES_TABLE . " where o_id = ? order by id desc LIMIT 1 ", $activity->getId());
        } elseif(method_exists($activity, 'getId')) {
            $row = $db->fetchRow("select *, column_json(attributes) as attributes from " . self::ACTIVITIES_TABLE . " where a_id = ? order by id desc LIMIT 1 ", $activity->getId());
        }

        if(!is_array($row)) {
            return false;
        }


        $entry = new DefaultActivityStoreEntry($row);

        return $entry;
    }

    public function getActivityDataForCustomer(CustomerInterface $customer) {
        $db = Db::get();

        $result = $db->fetchAll("select id,activityDate,type,o_id,a_id,md5,creationDate,modificationDate,COLUMN_JSON(attributes) as attributes from " . self::ACTIVITIES_TABLE . " where customerId = ? order by activityDate asc", [$customer->getId()]);

        foreach($result as $key => $value) {
            if($value['attributes']) {
                $result[$key]['attributes'] = \Zend_Json::decode($value['attributes']);
            }
        }

        return $result;
    }

    public function getActivityList() {
        return new DefaultMariaDbActivityList();
    }

    public function getActivitiesData($pageSize, $page = 1, ExportActivitiesFilterParams $params)
    {
        $db = Db::get();

        $timestamp = time();

        $select = $db->select();
        $select
            ->from(self::ACTIVITIES_TABLE,
                [
                    'id',
                    'activityDate',
                    'type',
                    'implementationClass',
                    'o_id',
                    'a_id',
                    'md5',
                    'creationDate',
                    'modificationDate',
                    'attributes' => 'COLUMN_JSON(attributes)'
                ]
                )
            ->order("activityDate asc")
        ;

        if($ts = $params->getModifiedSinceTimestamp()) {
            $select->where("modificationDate >= ?", $ts);
        }

        $paginator = new \Zend_Paginator(new \Zend_Paginator_Adapter_DbSelect($select));
        $paginator->setItemCountPerPage($pageSize);
        $paginator->setCurrentPageNumber($page);

        $data = [];
        foreach($paginator as $value) {
            if($value['attributes']) {
                $value['attributes'] = \Zend_Json::decode($value['attributes']);
            }

            $data[] = $value;
        }

        return [
            'page' => $page,
            'totalPages' => $paginator->getPages()->pageCount,
            'timestamp' => $timestamp,
            'data' => $data
        ];
    }

    public function getDeletionsData($entityType, $deletionsSinceTimestamp) {
        $db = Db::get();

        $sql = "select * from " . self::DELETIONS_TABLE . " where entityType = " . $db->quote($entityType) . " and creationDate >= " . $db->quote($deletionsSinceTimestamp);

        return [
            'data' => $db->fetchAll($sql)
        ];
    }

    public function deleteActivity(ActivityInterface $activity) {

        $db = Db::get();
        $row = false;
        if($activity instanceof Concrete) {
            $row = $db->fetchRow("select * from " . self::ACTIVITIES_TABLE . " where o_id = ? ", $activity->getId());
        } elseif(method_exists($activity, 'getId')) {
            $row = $db->fetchRow("select * from " . self::ACTIVITIES_TABLE . " where a_id = ? ", $activity->getId());
        }

        if($row) {
            $db->beginTransaction();

            try {
                $db->query("delete from " . self::ACTIVITIES_TABLE . " where id = " . intval($row['id']));

                $db->insertOrUpdate(self::DELETIONS_TABLE, [
                    'id' => $row['id'],
                    'creationDate' => time(),
                    'entityType' => 'activities',
                    'type' => $activity->cmfGetType()
                ]);

                $db->commit();
            } catch(\Exception $e) {
                print $e->getMessage();
                print "rollback";
                $e->rollback();
            }

        }
    }

    public function deleteCustomer(CustomerInterface $customer) {

    }

    protected function createDbRowData(ActivityInterface $activity) {

        $db = Db::get();

        $time = time();

        $attributes = $activity->cmfToArray();

        $data = [
            'customerId' => $activity->getCustomer()->getId(),
            'type' => $db->quote($activity->cmfGetType()),
            'implementationClass' => $db->quote(get_class($activity)),
            'o_id' => $activity instanceof Concrete ? $activity->getId() : '',
            'a_id' => !($activity instanceof Concrete) && method_exists($activity, 'getId') ? $activity->getId() : null,
            'activityDate' => $activity->cmfGetActivityDate()->getTimestamp(),
            'attributes' => \CustomerManagementFramework\Service\MariaDb::getInstance()->createDynamicColumnInsert($attributes),
        ];

        $data['md5'] = $db->quote(md5(serialize($data)));
        $data['modificationDate'] = $time;

        return $data;
    }

    public function getEntryById($id) {

        $db = Db::get();

        if($row = $db->fetchRow(sprintf("select *, column_json(attributes) as attributes from %s where id = ?", self::ACTIVITIES_TABLE), $id)) {
            return new DefaultActivityStoreEntry($row);
        }
    }
}