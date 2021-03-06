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
            <div class="col-sm-4 col-md-3">
                <?=self::writeForm($model)?>
            </div>
            <div class="col-sm-4 col-md-9">
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
    function updateModel(model, data)
        {
        model.keywords (data.keywords.map (function (r) { return ko.mapping.fromJS(r); }));
        }
    function initializeModel($model)
        {
        $model.executing = ko.observable (false);
        $model.analyze = function ()
            {
            ajaxCall($model.baseUrl, 'kwd', { kwd: $model.kwd() }, $model, $model.executing,
                     function (data) { updateModel ($model, data); }, $('#upload-form'));
            }
        $model.filtered = ko.computed (function ()
            {
            var ret = $.grep ($model.keywords(), function (el)
                {
                return ko.unwrap(ko.unwrap(el).competition) != 'High';
                });
            ret.sort (function (aObservable, bObservable)
                {
                var a = ko.unwrap(aObservable);
                var b = ko.unwrap(bObservable);
                if (ko.unwrap(a.competition) != ko.unwrap(b.competition))
                    return ko.unwrap(a.competition) == 'Low' ? -1 : 1;
                if (ko.unwrap(a.volume) != ko.unwrap(b.volume))
                    return ko.unwrap(b.volume) - ko.unwrap(a.volume);
                return ko.unwrap(a.cpc) - ko.unwrap(b.cpc);
                });
            return ret;
            });
        }
</script>
<?php
        Common::writeModelBindScripts($model->getJSON());
        }

    public static function writeBody ()
        {
?>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Keyword</th>
                <th>Volume</th>
                <th>Competition</th>
                <th>Avg CPC</th>
            </tr>
        </thead>
        <tbody data-bind="foreach: filtered">
            <tr>
                <td data-bind="text:keyword"></td>
                <td data-bind="text:volume"></td>
                <td data-bind="text:competition"></td>
                <td>
                    <span data-bind="text:bidLow"></span>
                            -
                    <span data-bind="text:bidHigh"></span>
                    <span data-bind="text:currency"></span>
                </td>
            </tr>
        </thead>
    </table>
<?php
        }

    public static function writeForm ()
        {
?>
    <form id="upload-form" enctype="multipart/form-data">
        <div class="form-group">
            <label for="kwd">Keyword</label>
            <input type="text" class="form-control" id="kwd" name="kwd" aria-describedby="kwdHelp" placeholder="Enter keyword" data-bind="value: kwd">
            <small id="kwdHelp" class="form-text text-muted">Enter keyword to analyze.</small>
        </div>
        <div class="form-group">
            <label for="csv">Keyword</label>
            <input type="file" class="form-control" id="csv" name="csv" aria-describedby="csvHelp" placeholder="Upload csv">
            <small id="csvHelp" class="form-text text-muted">Upload keywords generated by Google Keyword Planner.</small>
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
