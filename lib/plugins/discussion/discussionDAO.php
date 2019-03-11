<?php
namespace dokuwiki\discussion;
use Doctrine\DBAL\Driver\PDOException;
use PDO;
class discussionDAO
{
    public $settings;
    private $pdo;
    public function __construct()
    {
        require_once dirname(__FILE__).'/settings.php';
        $dsn = "mysql:host=".$this->settings['host'].
            ";dbname=".$this->settings['dbname'].
            ";port=".$this->settings['port'].
            ";charset=".$this->settings['charset'];
        try {
            $this->pdo = new PDO($dsn, $this->settings['username'], $this->settings['password']);
        } catch ( PDOException $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * Save users' comment into DB
     *
     * @param $data   the comment data
     * @param $cid  comment id
     * @param $xhtml   the content of comment
     * @param $username username
     * @param $parent parent comment id
     * @param $ID pageid
     * @return bool
     */
    public function insertComment($data, $cid, $xhtml, $username, $parent, $ID, $userid) {
        $sql = 'insert into '. $this->settings['comment'].' (hash, comment, time, username, pageid, parent, display, deleted, parentname, userid)
            values
                (:hash, :comment, null, :username, :pageid, :parent, true, false, :parentname, :userid)';
        try {
            $parentname = null;
            if (isset($parent)) {
                $parentname = $data['comments'][$parent]['user']['name'];
            }
            $statement = $this->pdo->prepare($sql);
            $statement->bindValue(':hash', $cid);
            $statement->bindValue(':comment', $xhtml);
            $statement->bindValue(':username', $username);
            $statement->bindValue(':pageid', $ID);
            $statement->bindValue(':parent', $parent);
            $statement->bindValue(':parentname', $parentname); // parent id
            $statement->bindValue(':userid', $userid);
            $result = $statement->execute();
            return $result;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }

    }

    /**
     * delete comment in DB
     *
     * @param $cid comment id
     * @return bool
     */
    public function delComment($cid) {
        try {
            $sql = 'delete from '.$this->settings['comment']. ' where hash = :hash';
            $statement = $this->pdo->prepare($sql);
            $statement->bindValue(':hash', $cid);
            $result = $statement->execute();
            return $result;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * get comment data
     *
     * @param $ID page id
     * @param $comperpage the comment number of one page
     * @param $id the id field value
     * @param $pagenum the page number
     * @return bool|array
     */
    public function selectData($ID, $comperpage, $id, $pagenum) {
        try {
            if ($pagenum == 1)
                $sql = 'select * from '.$this->settings['comment']. ' where pageid = :pageid And id >= :id order by id desc limit :comperpage';
            else
                $sql = 'select * from '.$this->settings['comment']. ' where pageid = :pageid And id < :id order by id desc limit :comperpage';
            $statement = $this->pdo->prepare($sql);
            $statement->bindValue(':pageid', $ID);
            $statement->bindValue(':id', $id, PDO::PARAM_INT);
            $statement->bindValue(':comperpage', $comperpage, PDO::PARAM_INT);
            $statement->execute();
            $res = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $res;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * delete data in pagination
     *
     * @return bool
     */
    public function delPagination($ID) {
        try {
            $sql = 'delete from '.$this->settings['pagination'].' where pageid = :pageid'; ;
            $statement = $this->pdo->prepare($sql);
            $statement->bindValue(':pageid', $ID);
            $res = $statement->execute();
            return $res;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * insert a group of data into pagination table
     *
     * @return bool
     */
    public function groupIstPagination($ID, $comperpage) {
        try {
            $res = $this->getAllValueOfId($ID);
            $totalpagenum = ceil(count($res) / $comperpage);
            for ($i=1; $i <= $totalpagenum; $i++) { 
                if ($i == $totalpagenum)
                    $id = $res[count($res)-1]['id'];
                else
                    $id = $res[$i*5-1]['id']; 
                $result = $this->insertPagination($id, $i, $ID);
            }
            return $result;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * get the id value of the last data of the page
     *
     * @return bool|array
     */
    public function getOnePageId($ID, $pagenum) {
        try {
            $sql = 'select comid from '.$this->settings['pagination'].' where pageid = :pageid And pagenum = :pagenum order by comid' ;
            $statement = $this->pdo->prepare($sql);
            $statement->bindValue(':pageid', $ID);
            $statement->bindValue(':pagenum', $pagenum);
            $statement->execute();
            $res = $statement->fetch(PDO::FETCH_ASSOC);
            return $res;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * get parent comment by hash
     *
     * @return bool|array
     */
    public function getCommentDataByHash($ID, $hash) {
        try {
            $sql = 'select * from '.$this->settings['comment'].' where pageid = :pageid And hash = :hash' ;
            $statement = $this->pdo->prepare($sql);
            $statement->bindValue(':pageid', $ID);
            $statement->bindValue(':hash', $hash);
            $statement->execute();
            $res = $statement->fetch(PDO::FETCH_ASSOC);
            return $res;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * get all value of id field by pageid
     *
     * @return bool|array
     */
    public function getAllValueOfId($ID) {
        try {
            $sql = 'select id from '.$this->settings['comment'].' where pageid = :pageid order by id desc' ;
            $statement = $this->pdo->prepare($sql);
            $statement->bindValue(':pageid', $ID);
            $statement->execute();
            $res = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $res;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * insert a group of data into pagination table
     *
     * @return bool
     */
    protected function insertPagination ($id, $pagenum, $pageid) {
        try {
            $sql = 'insert into '.$this->settings['pagination'].' (comid, pagenum, pageid) values (:comid, :pagenum, :pageid)';
            $statement = $this->pdo->prepare($sql);
            $statement->bindValue(':comid', $id);  
            $statement->bindValue(':pagenum', $pagenum);
            $statement->bindValue(':pageid', $pageid);
            $result = $statement->execute();
            return $result;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

}