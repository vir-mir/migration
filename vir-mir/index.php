<?
    error_reporting(E_ALL);
    set_time_limit(0);
    session_start();

    function autoload($name) {
        $name = array_merge((array)'lib', explode('\\', $name));
        $name = __DIR__ . '/migration/' . implode('/', $name) . '.php';
        include_once $name;
    }

    if (isset($_GET['reversion'])) {
        $_SESSION['reversion'] = $_GET['reversion'];
    } elseif (!isset($_SESSION['reversion'])) {
        $_SESSION['reversion'] = null;
    }



    spl_autoload_register('autoload');
    $kernel = new \Migration\Kernel();
    $kernel->run($_SESSION['reversion']=='on');

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

    if (isset($_GET['action']) && $_GET['action']==='all') {
        $kernel->allMigration();
    }

    if (!empty($_GET)) {
        header('Location: ./');
        exit;
    }

    if (!file_exists($kernel->logName)) {
        $modifiedTable = $kernel->getModifiedTable();
        $newTable = $kernel->getNewTables();
    }

?>
<!DOCTYPE html>
<html>
<head>
    <title>Migration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="migration/media/css/bootstrap.min.css" rel="stylesheet">

    <style type="text/css">
        .well {
            background: #fff;
        }
    </style>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
</head>
<body style="padding: 70px 0;">
<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Навигация</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="./">Migration</a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="navbar-collapse" style="padding-top: 10px" id="bs-example-navbar-collapse-1">
            <? if (!empty($modifiedTable) || !empty($newTable)) : ?>
                <a href="./?action=all" onclick="return confirm('Вы уверенны что хотите выполнить все действия?')" class="btn btn-primary btn-sm">Выполнить все миграции</a>
            <? endif; ?>

            <?
                $reversion = $_SESSION['reversion']=='on'?'null':'on';
                $class = $_SESSION['reversion']=='on'?'danger':'warning';
            ?>


            <a href="./?reversion=<?=$reversion?>"
               title="<?=$kernel->getDbNameReliz()?> => <?=$kernel->getDbNameDebug()?>"
               class="pull-right btn btn-sm btn-<?=$class?>">
                <?=$kernel->getDbNameDebug()?> => <?=$kernel->getDbNameReliz()?>
            </a>

        </div><!-- /.navbar-collapse -->
    </div>
</nav>
<div class="container">

    <?
        if (file_exists($kernel->logName)) {
            ?>
                <pre><?=$kernel->getLog();?></pre>
                <a href="./" class="btn btn-primary">ОК!</a>
            <?
        }
    ?>

    <? if (isset($newTable['new'])) : ?>
        <div class="alert alert-info"><div class="well well-sm" style="margin-bottom: 0;">
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
        </div></div>
    <? endif; ?>

    <? if (isset($newTable['remove'])) : ?>
        <div class="alert alert-info"><div class="well well-sm" style="margin-bottom: 0;">
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
        </div></div>
    <? endif; ?>

    <? if (!empty($modifiedTable)) : ?>
        <div  class="panel-group" id="accordion">
            <? foreach($modifiedTable as $table=>$data): ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="collapse" data-parent="#accordion" href="#<?=$table?>">
                                Изменения в таблицы <strong><?=$table?></strong>
                            </a>
                        </h4>
                    </div>
                    <div id="<?=$table?>" class="panel-collapse collapse"><div class="panel-body">
                    <? if(isset($data['field'])): ?>
                        <div class="alert alert-info"><div class="well well-sm" style="margin-bottom: 0;">
                        <h3>Модификация Полей</h3>
                        <table class="table table-responsive table-striped table-hover">
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
                        </div></div>
                    <? endif; ?>





                    <? if(isset($data['index'])): ?>
                        <div class="alert alert-info"><div class="well well-sm" style="margin-bottom: 0;">
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
                        </div></div>
                    <? endif; ?>
                    </div></div>
                </div>
            <? endforeach; ?>
        </div>
    <? endif; ?>

</div>


<nav class="navbar navbar-inverse navbar-fixed-bottom" role="navigation">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="container">
        <a class="navbar-brand" href="http://blog.vir-mir.ru/page/autor/">&copy; Фирсов А.А
            <? if (date("Y") != '2014') : ?> 2014 - <? endif; ?><?=date("Y")?>г.
        </a>
    </div>
</nav>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://code.jquery.com/jquery.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="migration/media/js/bootstrap.min.js"></script>
<?
    if ($kernel->getCongig('remove_log')) {
        if (file_exists($kernel->logName)) {
            unlink($kernel->logName);
        }
    }
?>
</body>
</html>