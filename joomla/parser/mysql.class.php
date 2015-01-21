<?php

class mysqldb {

            var $conn;
            var $host;
            var $user;
            var $pass;
            var $dbname;
            var $db;
            var $prefix;

            function mysqldb($dbname, $dbhost = 'localhost', $dbuser = 'root', $dbpass = '', $prefix) { # most common config ? 
                $this->host = $dbhost;
                $this->user = $dbuser;
                $this->pass = $dbpass;
                $this->dbname = $dbname;
                $this->prefix = $prefix;
                $this->open();
            }

            function open() {
                return $this->check_conn('active');
            }

            function close() {
                return $this->check_conn('kill');
            }

            function select_db($dbname = null) {
                if (!($dbname || $this->dbname))
                    return FALSE;
                if ($dbname)
                    $this->dbname = $dbname;
                if (!$this->db = mysql_select_db($this->dbname, $this->conn)) {
                    echo "ERROR CAN'T CONNECT TO database " . $this->dbname;
                    return FALSE;
                } else {
                    return $this->db;
                }
            }

            function check_conn($action = '') {
                if (!$host = @mysql_get_host_info($this->conn)) {
                    switch ($action) {
                        case 'kill':
                            return $host;
                            break;
                        case 'check':
                            return $host;
                            break;
                        default:
                        case 'active':
                            if (!$this->conn = mysql_connect($this->host, $this->user, $this->pass)) {
                                echo "CONNECTION TO $this->host FAILED";
                                return FALSE;
                            }
                            $this->select_db($this->dbname);
                            return mysql_get_host_info($this->conn);
                            break;
                    }
                } else {
                    switch ($action) {
                        case 'kill':
                            mysql_close($this->conn);
                            $this->conn = $this->db = null;
                            return true;
                            break;
                        case 'check':
                            return $host;
                            break;
                        default:
                        case 'active':
                            return $host;
                            break;
                    }
                }
            }

            function query($string) {
                $string = str_replace("wle_", $this->prefix, $string);
                $res = mysql_query($string, $this->conn);
                if (mysql_error())
                    echo "INVALID QUERY: " . $string . " " . mysql_error();
                else
                    return $res;
            }

            function fetchAll($sql) {
                $res = $this->query($sql);
                $data = array();
                $i = 0;
                if (is_resource($res)) {
                    while ($line = mysql_fetch_array($res, MYSQL_ASSOC)) {
                        $data[$i++] = $line;
                    }
                }
                echo mysql_error();
                return $data;
            }

            function fetchRow($sql) {
                $res = $this->query($sql);
                if (is_resource($res)) {
                    $line = mysql_fetch_array($res, MYSQL_ASSOC);
                }
                echo mysql_error();
                return $line;
            }

            function fetchCol($sql) {
                $res = $this->query($sql);
                if (is_resource($res)) {
                    while ($line = mysql_fetch_array($res)) {
                        $data[] = $line[0];
                    }
                }
                return $data;
            }

            function fetchOne($sql) {
                $res = $this->query($sql);
                if (is_resource($res)) {
                    $line = mysql_fetch_array($res, MYSQL_NUM);
                }
                return $line[0];
            }

            function update($table, $values, $where) {
                $sql = "UPDATE `{$table}` SET ";
                foreach ($values as $key => $value) {
                    $sql.="`{$key}`='" . $value . "', ";
                }
                $sql = substr($sql, 0, strlen($sql) - 2);
                $sql.=" WHERE {$where};";

                return $this->query($sql);
            }

            function insert($table, $values) {
                $sql = "INSERT INTO `{$table}` (";
                $keys = "";
                $vals = "";
                foreach ($values as $key => $value) {
                    $keys.="`{$key}`, ";
                    $vals.="'" . $value . "', ";
                }
                $sql.=$keys;
                $sql = substr($sql, 0, strlen($sql) - 2);
                $sql.= ") VALUES (" . $vals;
                $sql = substr($sql, 0, strlen($sql) - 2);
                $sql.=");";
//', 'echo $sql;
//', 'exit(0);
                $this->query($sql);
                return mysql_insert_id();
            }

        }