<?php
/*-----------------------------------------------\
|                                                |
|  Author:       Danilow Alexandr (modos189)     |
|  Version:      0.3                             |
|  Project:      CMS                             |
|  package       CMS Fapos                       |
|  subpackege    Admin Panel module              |
|  license:      GNU GPL v3                      |
|                                                |
\-----------------------------------------------*/

include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';
 
$pageTitle = 'Установка плагинов';
 
if (!isset($_GET['ac'])) $_GET['ac'] = 'index';
$actions = array('index', 'notcat', 'install', 'more');
                    
if ( !in_array( $_GET['ac'], $actions ) ) $_GET['ac'] = 'index';

switch ( $_GET['ac'] ) {
    case 'index':    // главная страница 
        $content = index($pageTitle);
        break;
    case 'notcat':        
        $content = notCat();
        break;
    case 'install':         
        $content = catInstall();
        break;
    case 'more':
        $content = catMore();
        break;
    default:
        $content = index($pageTitle);
}

$pageNav = $pageTitle;
$pageNavl = '<a href="plugins.php">Установка плагинов</a>';

$footer = file_get_contents('template/footer.php');

$dp = new Document_Parser;

$content = $content.$footer;
include_once ROOT . '/admin/template/header.php';
echo $content;
    
function index(&$page_title) {
    global $FpsDB;
    $page_title = 'Установка плагинов';

    $serv_list_plugs = json_decode(file_get_contents('https://raw.github.com/modos189/FaposCMS-plugins/master/list.json'), true);

    $content = '';

    $content = '<style>
            a .save-button {
                line-height: 42px;
                text-align: center;
                margin-bottom: 5px;
            }

            .button div {
                background: url("template/img/sbm-button-bg.jpg") repeat-x scroll 0 0 transparent;
                border: 4px solid #343333;
                color: #00E600;
                cursor: pointer;
                font-size: 10px;
                font-weight: bold;
                height: 15px;
                letter-spacing: 1px;
                line-height: 15px;
                padding: 0 10px 2px;
                text-transform: uppercase;
                margin-left: 5px;
            }
            select, input[type="file"] {
                background: #f5f5f5;
            }
            .setting-item {
                min-height: 180px;
                padding: 7px;
            }

            .setting-item .name {
                font-size: 160% !important;
            }

            .setting-item .comment {
                font-size: 100% !important;
            }

            .setting-item img {
                padding: 5px;
                box-shadow: 0 1px 3px #000;
                margin: 5px;
                width: 100px;
                max-height: 200px;
                background: #fff;
            }
            </style>
        ';
    
    $content .= "<div class=\"list\"><div class=\"title\">Установка из архива</div>
        <div class=\"level1\" style=\"margin-bottom: 15px;\">


        <div style=\"padding:7px;\">
        Вы можете найти плагины на <a href=\"http://fapos.net\">fapos.net</a>.
        Поддерживаются zip архивы размером не более 10 МиБ<br>
        <br>
        <form method=\"post\" action=\"install_plugins.php?ac=notcat\" enctype=\"multipart/form-data\">
        <font style=\"font-weight: bold;\">Установить с URL-адреса</font><br>
        <input type=\"url\" maxlength=\"255\" size=\"60\" value=\"\" name=\"plugin_url\"><br>
        Пример: <i>http://fapos.net/files/plugins/name.tar.gz</i>
        <br><br>
        <font style=\"font-weight: bold;\">или</font>
        <br><br>
        <font style=\"font-weight: bold;\">Загрузите архив для установки</font><br>
        <input type=\"file\" size=\"60\" name=\"plugin_upload\"><br>
        Пример: файл <i>name.tar.gz</i> на домашнем компьютере.
        <br><br>
        <input type=\"submit\" value=\"Установка\" name=\"send\" class=\"save-button\">
        </div></div>
        </form></div>";

    if (count($serv_list_plugs) < 1) return $content .= '<div class="warning">Каталог плагинов недоступен<br><br></div>';

    $content .= '<div class="list"><div class="level1"><div class="items">';
    foreach ($serv_list_plugs as $result) {
        $content .= "<div class=\"setting-item\"><span class=\"name\">".$result['name']."</span>";

        $content .= "<div style=\"float: right; display: table;\">";
        $content .= "<a style=\"text-decoration: none\" href='install_plugins.php?ac=install&url=".$result['url']."'><div class=\"save-button\">Установить</div></a>";
        if (!empty($result['more'])) {
            $content .= "<a style=\"text-decoration: none\" href='".$result['more']."' target=\"_blank\"><div class=\"save-button\">Подробнее</div></a>";
        }
        $content .= "</div><div>";

        if (!empty($result['image'])) {
            $content .= "<div style=\"float: left;\"><a class=\"gallery\" rel=\"group\" href=\"".$result['image']."\"><img src=\"".$result['image']."\"></a></div>";
        }

        $content .= "</div><span class=\"comment\" style=\"padding-bottom: 30px;\">".$result['desc'].'</span>';

        $content .= "<div style=\"clear: both\"></div></div>";
    }
    $content .= '</div></div></div>';
    
    return $content;
  
}



// установка по адресу или выгрузка
function notCat() {
    $content = "<div class=\"list\"><div class=\"level1\" style=\"padding: 10px;\">";


    if (!empty($_POST['plugin_url'])) {
        $filename = download($_POST['plugin_url']);
        if (empty($filename)) return 'Загрузка не удалась';
    } elseif (!empty($_FILES['plugin_upload']['name']) && is_uploaded_file($_FILES["plugin_upload"]["tmp_name"])) {
        if ($_FILES['plugin_upload']['size'] > 10*1024*1024*8) return 'Файл слишком большой ('.$_FILES['plugin_upload']['size']/1024/1024 . ' МиБ)';
        if (move_uploaded_file($_FILES["plugin_upload"]["tmp_name"], ROOT . '/sys/tmp/'.$_FILES["plugin_upload"]["name"])) {
            $filename = $_FILES['plugin_upload']['name'];
            $content .= unzip_and_install($filename);
        } else {
            $content .= 'Ошибка выгрузки';
        }
    }

    $content .= '</div></div>';

    return $content;
}



// установка из каталога
function catInstall() {
    $content = "<div class=\"list\"><div class=\"level1\" style=\"padding: 10px;\">";

    if (empty($_GET['url'])) redirect('/');
    $url = $_GET['url'];

    $filename = download($url);
    if (empty($filename)) {
        $content .= 'Загрузка не удалась';
    } else {
        $content .= unzip_and_install($filename);
    }

    $content .= '</div></div>';

    return $content;
}




function download($url) {
    // определение реального имени файла
    $headers = get_headers($url,1);
    $a = $headers['Content-Disposition'];
    preg_match('#filename="(.*)"#iU', $a, $matches);
    $filename = (isset($matches[1])) ? $matches[1] : basename($url);
    if (copy($url, ROOT . '/sys/tmp/'.$filename)) {
        return $filename;
    } else {
        return;
    }
}




function unzip_and_install($filename) {
    $zip = new ZipArchive;
    if ($zip->open(ROOT.'/sys/tmp/'.$filename) === true){
        mkdir(ROOT . '/sys/tmp/install_plugin_tmp', 0777);
        $zip->extractTo(ROOT . '/sys/tmp/install_plugin_tmp');
        $zip->close();
    } else {
        return 'Не могу найти файл архива! '.ROOT.'/sys/plugins/'.$filename;
    }

    $files = GetDirFilesR(ROOT . '/sys/tmp/install_plugin_tmp');
    $qwe = searchSys(ROOT . '/sys/tmp/install_plugin_tmp');
    if ($qwe) {
        copyr(substr($qwe, 0, -4), ROOT);
    } else {
        $qwe = searchPlugin(ROOT . '/sys/tmp/install_plugin_tmp');
        if ($qwe) {
            copyr($qwe, ROOT.'/sys/plugins/');
        } else {
            return 'Не могу определить способ установки';
        }
    }
    
    removeDirectory(ROOT . '/sys/tmp/install_plugin_tmp');

    return 'Плагин успешно установлен. Добавлены файлы: <br><br>'.$files;
}




function removeDirectory($path) {
    if(is_file($path)) return unlink($path);
        
    $dh=opendir($path);
    while (false!==($file=readdir($dh))) {
        if($file=='.'||$file=='..') continue;
        removeDirectory($path."/".$file);
    }
    closedir($dh);
        
    return rmdir($path);
}



// поиск папки с структурой движка в плагине
function searchSys($path) {
    if(is_file($path)) return;
    $objs=glob($path.'/*');
    foreach($objs as $obj) {
        if (substr_count($obj, 'sys')=="2") {
            return $obj;
        }
        $vik = searchSys($path."/".(substr(strrchr($obj, "/"), 1)));
        if ($vik) return $vik;
    }
}



// поиск папки с плагином в архиве
function searchPlugin($path) {
    if(is_file($path)) return;
    $objs=glob($path.'/*');
    foreach($objs as $obj) {
        print($obj);
        if (substr_count($obj, 'before')=="1" or substr_count($obj, 'after')=="1") {
            preg_match('/(.*)\\//i', $obj, $ret);
            // возвратить только путь, а не саму папку
            return $ret[1];
        }
        $vik = searchPlugin($path."/".(substr(strrchr($obj, "/"), 1)));
        if ($vik) return $vik;
    }
}



// функция рекурсивного копирования
function copyr($source, $dest)
{
    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }
 
    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest);
    }
   
    // If the source is a symlink
    if (is_link($source)) {
        $link_dest = readlink($source);
        return symlink($link_dest, $dest);
    }
 
    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }
 
        // Deep copy directories
        if ($dest !== "$source/$entry") {
            copyr("$source/$entry", "$dest/$entry");
        }
    }
 
    // Clean up
    $dir->close();
    return true;
}




function GetDirFilesR($path)
    {   
        $ret = null;
        $dir_iterator = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            if($file->isFile())
            $ret .= $file.'<br>';
        }
        return $ret;
    }

?>

<?php
if (!empty($_SESSION['info_message'])):
?>
<script type="text/javascript">showHelpWin('<?php echo h($_SESSION['info_message']) ?>', 'Сообщение');</script>
<?php
    unset($_SESSION['info_message']);
endif;
?>