<?php

/**
 * Author : AVONTURE Christophe - https://www.avonture.be.
 *
 * Based on the work of @cafewebmaster.com and of Bernhard Waldbrunner,
 * PHP grep, Copyright (C) 2012
 */

define('DEBUG', false);
define('DEMO', false);
define('REPO', 'https://github.com/cavo789/php_grep');

set_time_limit(0);

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class aeSecureFct
{
    /**
     * Safely read posted variables.
     *
     * @param type  $name    f.i. "password"
     * @param type  $type    f.i. "string"
     * @param type  $default f.i. "default"
     * @param mixed $base64
     *
     * @return type
     */
    public static function getParam($name, $type = 'string', $default = '', $base64 = false)
    {
        $tmp    = '';
        $return = $default;

        if (isset($_POST[$name])) {
            if (in_array($type, ['int', 'integer'])) {
                $return = filter_input(INPUT_POST, $name, FILTER_SANITIZE_NUMBER_INT);
            } elseif ('boolean' == $type) {
                // false = 5 characters
                $tmp    = substr(filter_input(INPUT_POST, $name, FILTER_SANITIZE_STRING), 0, 5);
                $return = (in_array(strtolower($tmp), ['on', 'true'])) ? true : false;
            } elseif ('string' == $type) {
                $return = filter_input(INPUT_POST, $name, FILTER_SANITIZE_STRING);
                if (true === $base64) {
                    $return = base64_decode($return);
                }
            } elseif ('unsafe' == $type) {
                $return = $_POST[$name];
            }
        } else { // if (isset($_POST[$name]))
            if (isset($_GET[$name])) {
                if (in_array($type, ['int', 'integer'])) {
                    $return = filter_input(INPUT_GET, $name, FILTER_SANITIZE_NUMBER_INT);
                } elseif ('boolean' == $type) {
                    // false = 5 characters
                    $tmp    = substr(filter_input(INPUT_GET, $name, FILTER_SANITIZE_STRING), 0, 5);
                    $return = (in_array(strtolower($tmp), ['on', 'true'])) ? true : false;
                } elseif ('string' == $type) {
                    $return = filter_input(INPUT_GET, $name, FILTER_SANITIZE_STRING);
                    if (true === $base64) {
                        $return = base64_decode($return);
                    }
                } elseif ('unsafe' == $type) {
                    $return = $_GET[$name];
                }
            }
        }

        if ('boolean' == $type) {
            $return = (in_array($return, ['on', '1']) ? true : false);
        }

        return $return;
    }

    /**
     * Scan a folder recursively and list files containing a specific pattern.
     *
     * $folder  Mandatory : Folder where to start the search
     * $query   Mandatory : String to search
     * $filter  Optional : Filter for file's restriction (like *.php)
     * $links   True/False = follow symbolic links or not
     * $regex   True/False = is the $query parameter contains a regular expression or not
     *
     * @param mixed $folder
     * @param mixed $query
     * @param mixed $filter
     * @param mixed $links
     * @param mixed $regex
     */
    public static function php_grep($folder, $query, $filter, $links, $regex)
    {
        $fp  = opendir($folder);
        $ret = '';

        while (false !== ($f = readdir($fp))) {
            $file_path = $folder . DS . $f;
            if ('.' == $f || '..' == $f || (!$links && is_link($file_path)) || (is_file($file_path) && !fnmatch($filter, $f))) {
                continue;
            }

            if (is_dir($file_path)) {
                $ret .= aeSecureFct::php_grep($file_path, $query, $filter, $links, $regex);
            } elseif ($regex ? preg_match($query, file_get_contents($file_path)) : stristr(file_get_contents($file_path), $query)) {
                $ret .= '<li>' . htmlspecialchars($file_path) . "</li>\n";
            }
        }

        closedir($fp);

        return $ret;
    }
}

if (DEBUG === true) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('html_errors', '1');
    ini_set('docref_root', 'http://www.php.net/');
    ini_set('error_prepend_string', "<div style='color:red; font-family:verdana; border:1px solid red; padding:5px;'>");
    ini_set('error_append_string', '</div>');
    error_reporting(E_ALL);
} else {
    ini_set('error_reporting', E_ALL & ~E_NOTICE);
}

$task = aeSecureFct::getParam('task', 'string', '', false);

// Folder where to start the scan
$default = str_replace('/', DS, dirname($_SERVER['SCRIPT_FILENAME'])); //get absolute path of this file
$folder  = trim(aeSecureFct::getParam('path', 'string', $default, true));

// String to search
$query = trim(aeSecureFct::getParam('query', 'string', '', true));

// Filter for file's restriction (like *.php)
$filter = trim(aeSecureFct::getParam('filter', 'string', '', true));

// follow symbolic links or not
$links = aeSecureFct::getParam('links', 'boolean', false, false);

// is the $query parameter contains a regular expression or not
$regex = aeSecureFct::getParam('regex', 'boolean', false, false);

if ('' != $task) {
    switch ($task) {
        case 'doIt':
            if ('' == $filter) {
                $filter = '*';
            }

            $results = '';
            if ('' !== $query) {
                $results = aeSecureFct::php_grep($folder, $query, $filter, $links, $regex);
                $results = ($results ? '<ol>' . $results . '</ol>' : '<p>Aucune occurence de <strong>' . $query . '</strong> n\'a été trouvée dans le dossier <strong>' . $folder . '</strong> ' .
                    '(et sous-dossiers).</p>');
            }

            echo $results;

            break;
        case 'killMe':
            $return .= '<p class="text-success">Le script ' . __FILE__ . ' a &eacute;t&eacute; ' .
                'supprim&eacute; du serveur avec succ&egrave;s</p>';

            if (!DEMO) {
                // Don't kill when demo mode enabled
                unlink(__FILE__);
            }

            echo $return;

            break;
    }

    die();
}

// Get the GitHub corner
$github = '';
if (is_file($cat = __DIR__ . DIRECTORY_SEPARATOR . 'octocat.tmpl')) {
    $github = str_replace('%REPO%', REPO, file_get_contents($cat));
}

?>

<!DOCTYPE html>
<html lang="en">

   <head>
      <meta charset="utf-8"/>
      <meta name="author" content="Christophe Avonture" />
      <meta name="robots" content="noindex, nofollow" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
      <meta http-equiv="X-UA-Compatible" content="IE=9; IE=8;" />
      <title>PHP-Grep</title>
      <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
      <style>
          .ajax_loading {display:inline-block;
            width:32px;
            height:32px;
            margin-right:20px;
          }

        * {
            font-family: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
            font-size: 10pt;
        }

        label {
            width: 5em;
            display: inline-block;
        }

        ol {
            margin-left: 5em;
            padding-left: 0.3em;
        }

        input[type=submit] {
            font-size: 12pt;
        }
      </style>
   </head>

    <body>
        <?php echo $github; ?>
        <div class="container">
            <div class="page-header"><h1>PHP-grep</h1></div>
            <div class="container">
                <details>
                    <summary>How to use?</summary>
                    <div class="row">
                            <div class="col-sm">
                                <ul>
                                    <li>Update the folder if needed, where to start the search</li>
                                    <li>Enter the text to search for in the Expression area</li>
                                    <li>Specify a filter like *.php (or nothing to scan all files)</li>
                                    <li>Click on the "Start the search" button</li>
                                </ul>
                            </div>
                            <div class="col-sm">
                                <img height="300px" src="https://raw.githubusercontent.com/cavo789/php_grep/master/images/demo.gif" alt="Demo">
                            </div>
                        </div>
                    </div>
                </details>
                <br/>
                <div id="intro">

                    <form class="form-horizontal">
                        <div class="form-group">
                            <label for="path" class="col-sm-2 control-label">Folder:</label>
                            <div class="col-sm-10">
                                <input type="text" id="path" name="path" size="70" 
                                    class="form-control"value="<?php echo $folder; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="query" class="col-sm-2 control-label">Expression:</label>
                            <div class="col-sm-10">
                                <input type="text" id="query" name="query" size="70" 
                                    class="form-control"placeholder="Text you are looking for" 
                                    value="<?php echo $query; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="filter" class="col-sm-2 control-label">Filter on files:</label>
                            <div class="col-sm-10">
                                <input type="text" id="filter" name="filter" size="30" 
                                    placeholder="For example: *.php, *.css, *.js, ... or * for all files" 
                                    class="form-control"value="<?php echo $filter; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <div class="checkbox">
                                    <input type="checkbox" id="links" name="links" <?php echo $links ? 'checked="checked"' : ''; ?> />
                                    <label for="links" class="control-label">Follow&nbsp;the&nbsp;symbolic&nbsp;links</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <div class="checkbox">
                                    <input class="checkbox" id="regex" name="regex" <?php echo $regex ? 'checked="checked"' : ''; ?> />
                                    <label for="regex" class="control-label">Regular&nbsp;expressions</label>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="row">
                        <button type="button" id="btnDoIt" class="btn btn-primary">Start the search</button>
                        <button type="button" id="btnKillMe" class="btn btn-danger pull-right" style="margin-left:10px;">
                            Remove this script</button>
                    </div>

                    <br/>

                </div>
                <div id="Result">&nbsp;</div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
        <script type="text/javascript" src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
        <script type="text/javascript">

            $('#btnDoIt').click(function(e)  {

                e.stopImmediatePropagation();

                var $data = new Object;
                $data.task = "doIt"
                $data.path = btoa($("#path").val());
                $data.query = btoa($("#query").val());
                $data.filter = btoa($("#filter").val());
                $data.links = $("#links").is(":checked")?1:0;
                $data.regex = $("#regex").is(":checked")?1:0;

                $.ajax({
                    beforeSend: function() {
                        $('#btnDoIt').prop("disabled", true);
                        $('#btnKillMe').prop("disabled", true);
                        $('#Result').html('<div><span class="ajax_loading">&nbsp;</span><span style="font-style:italic;font-size:1.5em;">Un peu de patience svp...</span></div>');
                    },
                    async:true,
                    type:"<?php echo DEBUG ? 'GET' : 'POST'; ?>",
                    url: "<?php echo basename(__FILE__); ?>",
                    data:$data,
                    datatype:"html",
                    success: function (data) {
                        $('#Result').html(data);
                        $('#btnDoIt').prop("disabled", false);
                        $('#btnKillMe').prop("disabled", false);
                    }
                });
            });

            // Remove this script
            $('#btnKillMe').click(function(e)  {
                e.stopImmediatePropagation();

                var $data = new Object;
                $data.task = "killMe";

                $.ajax({
                    beforeSend: function() {
                       $('#Result').empty();
                       $('#btnDoIt').prop("disabled", true);
                       $('#btnKillMe').prop("disabled", true);
                    },
                    async:true,
                    type:"<?php echo DEBUG ? 'GET' : 'POST'; ?>",
                    url:"<?php echo basename(__FILE__); ?>",
                    data:$data,
                    datatype:"html",
                    success: function (data) {
                       $('#intro').remove();
                       $('#Result').html(data);
                    }
                });
            });

        </script>

    </body>
</html>
