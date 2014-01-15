<?
    error_reporting(E_ALL);
    set_time_limit(0);

    function autoload($name) {
        $name = array_merge((array)'lib', explode('\\', $name));
        $name = __DIR__ . '/../../' . implode('/', $name) . '.php';
        include_once $name;
    }

    spl_autoload_register('autoload');
    $kernel = new \Migration\Kernel();
    $kernel->run();

    if (isset($_GET['action']) && $_GET['action']==='add_table') {
        $kernel->addTable($_GET['table']);
    }

    if (isset($_GET['action']) && $_GET['action']==='remove_table') {
        $kernel->removeTable($_GET['table']);
    }

    if (isset($_GET['action']) && $_GET['action']==='field') {
        if ($_GET['method']=='new') {
            $kernel->addField($_GET['table'], $_GET['field']);
        }
        if ($_GET['method']=='comparison') {
            $kernel->changeField($_GET['table'], $_GET['field']);
        }
        if ($_GET['method']=='remove') {
            $kernel->removeField($_GET['table'], $_GET['field']);
        }
    }

    if (isset($_GET['action']) && $_GET['action']==='index') {
        if ($_GET['method']=='new') {
            $kernel->addIndex($_GET['table'], $_GET['field']);
        }
        if ($_GET['method']=='comparison') {
            $kernel->changeIndex($_GET['table'], $_GET['field']);
        }
        if ($_GET['method']=='remove') {
            $kernel->removeIndex($_GET['table'], $_GET['field']);
        }
    }

    if (!empty($_GET)) {
        header('Location: /vir-mir/migration/lib/Migration/');
        exit;
    }



    $modifiedTable = $kernel->getModifiedTable();
    $newTable = $kernel->getNewTables();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Bootstrap 101 Template</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="../../media/css/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="container">
    <h1>Migration!</h1>

    <? if (isset($newTable['new'])) : ?>
        <h3>Новые таблицы</h3>
        <table class="table table-responsive table-hover">
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <? foreach($newTable['new'] as $table): ?>
                    <tr>
                        <td><?=$table?></td>
                        <td>
                            <a title="Добавить" href="?table=<?=$table?>&action=add_table" class="btn btn-primary btn-sm">
                                <i class="glyphicon glyphicon-plus"></i>
                            </a>
                        </td>
                    </tr>
                <? endforeach; ?>
            </tbody>
        </table>

    <? endif; ?>

    <? if (isset($newTable['remove'])) : ?>
        <h3>Затянуть таблицы</h3>
        <table class="table table-responsive table-hover">
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <? foreach($newTable['remove'] as $table): ?>
                    <tr>
                        <td><?=$table?></td>
                        <td>
                            <a title="Добавить" href="?table=<?=$table?>&action=remove_table" class="btn btn-primary btn-sm">
                                <i class="glyphicon glyphicon-plus"></i>
                            </a>
                        </td>
                    </tr>
                <? endforeach; ?>
            </tbody>
        </table>

    <? endif; ?>

    <? foreach($modifiedTable as $table=>$data): ?>

        <? if(isset($data['field'])): ?>
            <hr>
            <h3>Модификация Полей</h3>
            <table class="table table-responsive table-hover">
                <thead>
                <tr>
                    <th colspan="3" style="text-align: center;"><?=$table?></th>
                </tr>
                </thead>
                <tbody>
                <? foreach($data['field'] as $method=>$fieldData): ?>
                    <? if (empty($fieldData)) continue; ?>
                    <tr>
                        <td><?=$method?></td>
                        <td colspan="2">
                            <table class="table table-responsive table-hover">
                                <thead>
                                <tr>
                                    <th>Имя</th>
                                    <th>Даные</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                <? foreach($fieldData as $field=>$dataField): ?>
                                    <tr>
                                        <td><?=$field?></td>
                                        <td><?=implode(' ', $dataField)?></td>
                                        <td>
                                            <a title="Добавить"
                                               href="?table=<?=$table?>&action=field&field=<?=$field?>&method=<?=$method?>"
                                               class="btn btn-primary btn-sm">
                                                <i class="glyphicon glyphicon-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <? endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                <? endforeach; ?>
                </tbody>
            </table>
        <? endif; ?>





        <? if(isset($data['index'])): ?>
            <hr>
            <h3>Модификация индексов</h3>
            <table class="table table-responsive table-hover">
                <thead>
                <tr>
                    <th colspan="3" style="text-align: center;"><?=$table?></th>
                </tr>
                </thead>
                <tbody>
                <? foreach($data['index'] as $method=>$fieldData): ?>
                    <? if (empty($fieldData)) continue; ?>
                    <tr>
                        <td><?=$method?></td>
                        <td colspan="2">
                            <table class="table table-responsive table-hover">
                                <thead>
                                <tr>
                                    <th>Имя</th>
                                    <th>Даные</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                <? foreach($fieldData as $field=>$dataIndex): ?>
                                    <tr>
                                        <td><?=$field?></td>
                                        <td><?=$dataIndex?></td>
                                        <td>
                                            <a title="Добавить"
                                               href="?table=<?=$table?>&action=index&field=<?=$field?>&method=<?=$method?>"
                                               class="btn btn-primary btn-sm">
                                                <i class="glyphicon glyphicon-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <? endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                <? endforeach; ?>
                </tbody>
            </table>
        <? endif; ?>


    <? endforeach; ?>






</div>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://code.jquery.com/jquery.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="../../media/js/bootstrap.min.js"></script>
</body>
</html>