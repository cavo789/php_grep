<?php

/**
 * Author : AVONTURE Christophe - https://www.avonture.be.
 *
 * Based on the work of @cafewebmaster.com and of Bernhard Waldbrunner,
 * PHP grep, Copyright (C) 2012
 *
 * Last mod:
 * 2019-01-04 - Abandonment of jQuery and migration to vue.js
 */

define('DEBUG', false);
define('DEMO', false);
define('REPO', 'https://github.com/cavo789/php_grep');

set_time_limit(0);

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class AvontureFct
{
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
        $ret = [];

        while (false !== ($f = readdir($fp))) {
            $file_path = rtrim($folder, DS) . DS . $f;
            if ('.' == $f || '..' == $f || (!$links && is_link($file_path)) || (is_file($file_path) && !fnmatch($filter, $f))) {
                continue;
            }

            if (is_dir($file_path)) {
                $tmp = AvontureFct::php_grep($file_path, $query, $filter, $links, $regex);
                if (!empty($tmp)) {
                    $ret = array_merge($ret, $tmp);
                }
            } elseif ($regex ? preg_match($query, file_get_contents($file_path)) : stristr(file_get_contents($file_path), $query)) {
                $ret[] = htmlspecialchars($file_path);
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

// Retrieve posted data
$data = json_decode(file_get_contents('php://input'), true);

// Get absolute path of this file
$default = str_replace('/', DS, dirname($_SERVER['SCRIPT_FILENAME']));

// Folder where to start the scan
$folder = $default;

if ($data !== []) {
    $task = trim(filter_var(($data['task'] ?? ''), FILTER_UNSAFE_RAW));

    if (in_array($task, ['doSearch', 'doKill'])) {
        switch ($task) {
            case 'doSearch':
                // Get parameters

                // Folder where to start the scan
                $folder = rtrim(base64_decode(filter_var(($data['folder'] ?? $default), FILTER_UNSAFE_RAW)), DS) . DS;

                // String to search
                $query = trim(base64_decode(filter_var(($data['query'] ?? ''), FILTER_UNSAFE_RAW)));

                // Filter for file's restriction (like *.php)
                $filter = trim(base64_decode(filter_var(($data['filter'] ?? ''), FILTER_UNSAFE_RAW)));

                if ('' == $filter) {
                    $filter = '*';
                }

                // Follow symbolic links or not (boolean)
                $links = boolval(trim(filter_var(($data['links'] ?? ''), FILTER_UNSAFE_RAW)));

                // is the $query parameter contains a regular expression or not (boolean)
                $regex = boolval(trim(filter_var(($data['regex'] ?? ''), FILTER_UNSAFE_RAW)));

                $results = '';

                if ('' !== $query) {
                    $results = json_encode(AvontureFct::php_grep($folder, $query, $filter, $links, $regex));
                }

                header('Content-Type: text/json');
                echo $results;

                break;
            case 'doKill':

                if (!DEMO) {
                    // Don't kill when demo mode enabled
                    //unlink(__FILE__);
                }

                // return 0 when the file still exists
                $arr['removed'] = is_file(__FILE__) ? 0 : 1;
                echo json_encode($arr);

                break;
            default:
                echo 'Unsupported task';

                break;
        }

        die();
    }
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
        body {
              margin-top: 25px;
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

        .doKill {
            margin-left:10px;
        }
      </style>
   </head>

    <body>
        <?php echo $github; ?>
        <div id="app" class="container">
            <div class="page-header"><h1>PHP-grep</h1></div>
            <div class="container">
                <div v-if="showIntro">
                    <how-to-use demo="https://raw.githubusercontent.com/cavo789/php_grep/master/images/demo.gif">
                        <ul>
                            <li>Update the folder if needed, where to start the search</li>
                            <li>Enter the text to search for in the Expression area</li>
                            <li>Specify a filter like *.php (or nothing to scan all files)</li>
                            <li>Click on the "Start the search" button</li>
                        </ul>
                    </how-to-use>
                    <br/>
                    <form class="form-horizontal">
                        <div class="form-group">
                            <label for="path" class="col-sm-2 control-label">Folder:</label>
                            <div class="col-sm-10">
                                <input type="text" name="path" size="70" class="form-control"
                                v-model="folder" @change="doReset" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="query" class="col-sm-2 control-label">Expression:</label>
                            <div class="col-sm-10">
                                <input type="text" id="query" name="query" size="70"
                                    class="form-control" placeholder="Text you are looking for"
                                    v-model="query" @keydown="doReset" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="filter" class="col-sm-2 control-label">Filter on files:</label>
                            <div class="col-sm-10">
                                <input type="text" id="filter" name="filter" size="30"
                                    placeholder="For example: *.php, *.css, *.js, ... or * for all files"
                                    class="form-control" v-model="filter" @change="doReset" />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <div class="checkbox">
                                    <input type="checkbox" id="links" name="links"
                                        v-model="links" @change="doReset" />
                                    <label for="links" class="control-label">Follow&nbsp;the&nbsp;symbolic&nbsp;links</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <div class="checkbox">
                                    <input type="checkbox" id="regex" name="regex"
                                        v-model="regex" @change="doReset" />
                                    <label for="regex" class="control-label">Regular&nbsp;expressions</label>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="row">
                        <button type="button" @click="doSearch" class="btn btn-primary" :class="{ disabled: noQuery }">Start the search</button>
                        <button type="button" @click="doKill" class="btn btn-danger pull-right doKill">
                            Remove this script</button>
                    </div>

                    <br/>

                </div>

                <h2 v-if="isKilledSuccess" class="text-success">
                    The script has been successfully removed from the server
                </h2>

                <h2 v-if="isKilledFailure" class="text-danger" style="font-size:2em;">
                    An error has occurred while trying removing the script; please do it yourself
                </h2>

                <div v-if="noResult">
                    <p>No occurrence of <strong>{{ query }}</strong> has been found in folder
                        <strong>{{ folder }}</strong> (sub-folders included).</p>
                </div>

                <files-list :files="files" v-if="files.length>0"></files-list>

            </div>
        </div>
        <script src="https://unpkg.com/vue@2"></script>
        <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
        <script type="text/javascript">

            Vue.component('how-to-use', {
                props: {
                    demo: {
                        type: String,
                        required: true
                    }
                },
                template:
                    `<details>
                        <summary>How to use?</summary>
                        <div class="row">
                            <div class="col-sm">
                                <slot></slot>
                            </div>
                            <div class="col-sm"><img v-bind:src="demo" alt="Demo"></div>
                        </div>
                    </details>`
            });

            Vue.component('file', {
                template:
                    `<li><slot></slot></li>`
            });

            Vue.component('files-list', {
                props: {
                    files: {
                        type: Array,
                        required: true
                    }
                },
                template:
                    `<div>
                        <p style="text-decoration:underline;">{{ files.length }} file(s) found:</p>
                        <ol>
                            <file v-for="(file, key) in files" :key="key">{{ file }}</file>
                        </ol>
                    </div>`
            });

            var app = new Vue({
                el: '#app',
                data: {
                    folder: '<?php echo str_replace('\\', '\\\\', $folder);?>',
                    filter: '',
                    files: [],
                    isKilledSuccess: false,
                    isKilledFailure: false,
                    links: false,
                    query: '',
                    regex: false,
                    showIntro: true,
                    showResult: false
                },
                methods: {
                    doReset() {
                        this.showResult = false;
                        this.files = [];
                    },
                    doSearch() {
                        if(this.query!=='') {
                            var $data = {
                                task: 'doSearch',
                                folder: btoa(this.folder),
                                query: btoa(this.query),
                                filter: btoa(this.filter),
                                links: this.links ? 1 : 0,
                                regex: this.regex ? 1 : 0
                            }
                            axios.post('<?php echo basename(__FILE__); ?>', $data)
                                .then(response => {
                                    console.log('Found '+response.data.length + ' files');
                                    this.showResult = true;
                                    this.files = response.data;
                                })
                                .catch(function (error) {console.log(error);});
                        }
                    },
                    doKill() {
                        var $data = {
                            task: 'doKill'
                        }
                        axios.post('<?php echo basename(__FILE__); ?>', $data)
                            .then(response => {
                                this.isKilledSuccess = (response.data.removed == 1);
                                this.isKilledFailure = (response.data.removed == 0);
                                this.showIntro = false;
                            })
                            .catch(function (error) {console.log(error);});
                    }
                },
                computed: {
                    noQuery() {
                        return (this.query == '')
                    },
                    noResult() {
                        return (this.showResult && (this.files.length==0))
                    }
                }
            });
        </script>
    </body>
</html>
