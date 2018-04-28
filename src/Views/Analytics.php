<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace KeywordAnalytics\Views;

/**
 * Description of Analytics
 *
 * @author Andrius R. <knutux@gmail.com>
 */
class Analytics
    {
    public static function write (\KeywordAnalytics\Model $model)
        {
?>
<div class="content-fluid">
    <?= Common::writeBoundErrors()?>
    <div data-bind="if:'analytics'==mode()">
        <div id="categories-with-tasks" class="row">
            <div class="col-sm-12 col-md-12">
                <?=self::writeBody($model)?>
            </div>
        </div>
    </div>
</div>
<?=self::writeScripts($model)?>
<?=self::writeTemplates($model)?>

<?php
        }

    public static function writeScripts (\KeywordAnalytics\Model $model)
        {
        Common::writeCommonScripts();
?>
<script>
    function initializeModel($model)
        {
        $model.executing = ko.observable (false);
        $model.analyze = function ()
            {
            ajaxCall($model.baseUrl, 'kwd', { kwd: $model.kwd() }, $model, $model.executing, function () {});
            }
        }
</script>
<?php
        Common::writeModelBindScripts($model->getJSON());
        }

    public static function writeBody ()
        {
?>
    <form>
        <div class="form-group">
            <label for="kwd">Keyword</label>
            <input type="text" class="form-control" id="kwd" aria-describedby="kwdHelp" placeholder="Enter keyword" data-bind="value: kwd">
            <small id="kwdHelp" class="form-text text-muted">Enter keyword to analyze.</small>
        </div>
        <button type="submit" class="btn btn-primary" data-bind="click: analyze">Analyze</button>
    </form>
<?php
        }

    public static function writeTemplates ()
        {
?>
<?php
        }
    }
