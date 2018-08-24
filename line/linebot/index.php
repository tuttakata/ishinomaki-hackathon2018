<?php
$lib_dir = __DIR__ . '/lib';
set_include_path(get_include_path() . PATH_SEPARATOR . $lib_dir . '/PEAR');
//require_once 'MDB2.php';
require_once __DIR__ . '/config.php';

$select_sql = <<< SELECT_SQL
select *
from item;
SELECT_SQL;

$dbh = NULL;
try {
    // DB接続
    $dbh = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8;', DB_USER, DB_PASSWORD);

    $select_stmt = $dbh->prepare($select_sql);
    $select_stmt->execute();
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
        <head>
            <link rel="stylesheet" href="/linebot/css/bootstrap.css"　type="text/css" media="screen">
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
            <script src="/linebot/js/bootstrap.min.js"></script>
        </head>
        <body background="/linebot/img/syatyou.png" style="">
            <div class="container">
                <div class="row">
                    <?php
                    $count = 1;
                    while ($row = $select_stmt->fetch()) {
                        ?>
                        <div class="span4">
                            <h3>景品番号：<?php echo $count; ?>番</h3>
                            <p>
                                <!-- モーダルウィンドウを表示するボタンを設置 -->
                                <?php
                                    $clazz = 'btn-primary';
                                    if (!empty($row['prizewinner'])) {
                                        $clazz = 'btn-warning';
                                    }
                                ?>
                                <button type="button" class="btn <?php echo $clazz; ?>" data-toggle="modal" data-target="#myModal<?php echo $row['id']; ?>">
                                    <i class="icon-gift icon-white"></i>景品発表<i class="icon-gift icon-white"></i>
                                </button>
                                <!-- 表示されるモーダルウィンドウ -->
                                <div style="display: none;" class="modal fade" id="myModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-body">
                                                <img src="/linebot/img/<?php echo $row['img_src']; ?>" style="width: 100%;height: 100%;" alt="商品イメージ">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                <a href="/linebot/push.php?ID=<?php echo $row['id'];?>&group_id=<?php echo $row['group_id']; ?>&<?php echo rand() ?>;" class="btn btn-danger">抽選&当選者発表</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </p>
                        </div>
                        <?php
                        $count++;
                    }
                    ?>
                </div>
            </div>
            <?php
        } catch (Exception $e) {
            ob_start();
            var_dump($e->getMessage());
            $raw = ob_get_clean();
            file_put_contents('dump.txt', $raw . "\n=====================================\n", FILE_APPEND);
        }
        ?>
    </body>
</html>
