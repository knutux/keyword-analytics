<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace KeywordAnalytics\Views;

/**
 * Description of Common
 *
 * @author Andrius R. <knutux@gmail.com>
 */
class Common
    {
    const FIELD_USERNAME = "ch_user";
    const FIELD_PASSWORD = "ch_pwd";

    public static function writeHTMLHeader (string $title = null, string $description = null)
        {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<HTML>
<HEAD>
    <TITLE><?=$title?></TITLE>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
    <!--meta name="viewport" content="width=device-width, initial-scale=1"-->
    <META NAME="Description" CONTENT="<?=$description?>">
    <META NAME="Author" CONTENT="knutux@gmail.com">
    <!--link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous"-->
    <link href="https://maxcdn.bootstrapcdn.com/bootswatch/4.0.0/litera/bootstrap.min.css" rel="stylesheet" integrity="sha384-MmFGSHKWNFDZYlwAtfeY6ThYRrYajzX+v7G4KVORjlWAG0nLhv0ULnFETyWGeQiU" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.bundle.min.js" integrity="sha384-feJI7QwhOS+hwpX2zkaeJQjeiwlhOP+SdQDqhgvvo1DsjtiSQByFdThsxO669S2D" crossorigin="anonymous"></script>
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.4.2/knockout-min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.4.1/knockout.mapping.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.5/lodash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/URI.js/1.19.1/URI.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-treeview/1.2.0/bootstrap-treeview.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-treeview/1.2.0/bootstrap-treeview.min.js"></script>
    <script src="js/URI.fragmentQuery.js"></script>
    <script src="js/ko.extenders.urlSync.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.2.2/jquery.form.min.js" integrity="sha384-FzT3vTVGXqf7wRfy8k4BiyzvbNfeYjK+frTVqZeNDFl8woCbF0CYG6g2fMEFFo/i" crossorigin="anonymous"></script>
</HEAD>
<BODY>
    <h1><?=$title?></h1>
<?php
        }

    public static function writeHTMLFooter ()
        {
?>
</BODY>
</HTML>
<?php
        }

    public static function writeErrorAsRequired (string $error = null)
        {
        if (!empty ($error))
            {
?>
        <div class="alert alert-danger">
          <strong>Error!</strong> <?=$error?>
        </div>
<?php
            }
        }

    public static function writeBoundErrors (string $param = "errors")
        {
?>
    <div data-bind="visible: <?=$param?>" class="display:none">
    <div data-bind="foreach: <?=$param?>">
        <div class="alert alert-danger">
            <strong>Error!</strong> <span data-bind="text:$data"></span>
        </div>
    </div>
    </div>
<?php
        }

    public static function writeBoundMessages (string $param = "messages", string $status = "info")
        {
?>
    <div data-bind="foreach: <?=$param?>">
        <div class="alert alert-<?=$status?>">
            <span data-bind="text:$data"></span>
        </div>
    </div>
<?php
        }

    public static function writeLoginForm (string $title, string $buttonTitle = "Login", string $error = null)
        {
?>
    <!-- LOGIN FORM -->
    <div class="text-center" style="margin:50px auto; width:400px">
	<div class="logo"><?=$title?></div>
        <?=self::writeErrorAsRequired($error)?>
	<!-- Main Form -->
	<div class="login-form-1">
		<form id="login-form" class="text-left" method="POST">
			<div class="login-form-main-message"></div>
			<div class="main-login-form text-center">
				<div class="login-group">
					<div class="form-group">
						<label for="lg_username" class="sr-only">Username</label>
                                                <input type="text" class="form-control" id="<?=self::FIELD_USERNAME?>" name="<?=self::FIELD_USERNAME?>" placeholder="username">
					</div>
					<div class="form-group">
						<label for="lg_password" class="sr-only">Password</label>
						<input type="password" class="form-control" id="<?=self::FIELD_PASSWORD?>" name="<?=self::FIELD_PASSWORD?>" placeholder="password">
					</div>
				</div>
				<button type="submit" class="login-button"><i class="fa fa-chevron-right"></i> <?=$buttonTitle?></button>
			</div>
		</form>
	</div>
	<!-- end:Main Form -->
</div>
<?php
        }

    public static function writeModelBindScripts ($json, string $initializeFn = "initializeModel")
        {
?>
<script>
    $(function () {
        var viewModel = <?=$json?>;
        viewModel = ko.mapping.fromJS(viewModel);
        <?=empty ($initializeFn) ? "(function(){})" : $initializeFn?> (viewModel);
        try
            {
            ko.applyBindings(viewModel);
            }
        catch (err)
            {
            $('body').html (err.toString());
            }
    });
</script>
<?php
        }

    public static function writeCommonScripts ()
        {
?>
<script>
    function ajaxCall (baseUrl, fn, data, model, progress, successCallback, form)
        {
        if (progress())
            return; // already in the middle of the operation
        
        data['fn'] = fn;
        
        model.errors.removeAll();
        progress(true);
        var fn = form ? form.ajaxSubmit.bind(form) : $.ajax;
        fn(
            {
            type: "POST",
            url: ko.unwrap (baseUrl),
            data: data,
            success: function (data, textStatus, jqXHR)
                {
                progress(false);
                if (!data)
                    {
                    model.errors (["ERROR: null reqsponse"]);
                    }
                else if (data.errors && data.errors.length)
                    {
                    model.errors (data.errors);
                    }
                else if (data.success)
                    {
                    successCallback (data);
                    }
                else
                    model.errors ([jQuery(data).text()]);
                },
            error:  function (jqXHR, textStatus, errorThrown)
                {
                progress(false);
                if (jqXHR.responseJSON && jqXHR.responseJSON.errors)
                    model.errors(jqXHR.responseJSON.errors);
                model.errors.push ("Error - " + errorThrown);
                },
            });
        }
</script>
<?php
        }

    public static function writeEditForm (string $dialogId, array $params) : \stdClass
        {
        $dialogJSVariable = preg_replace('/[-]/', '_', $dialogId);
        $initFn = 'init'.$dialogJSVariable;
?>
 <div id="<?=$dialogId?>" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="<?=$dialogId?>-label" data-bind="with: <?=$dialogJSVariable?>">
  <div class="modal-dialog modal-lg" role="document" style="z-index: 2000">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="<?=$dialogId?>-label" data-bind="text:dialogTitle">Edit</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
         <div class="errors" data-bind="foreach: errors">
            <div class="alert alert-dismissible alert-danger"><p data-bind="text: $data"></p></div>
         </div>
        <form>
<?php
        $visibleParams = [];
        foreach ($params as $id => $def)
            {
            if ($def->readonly)
                continue;
            
            $visibleParams[$id] = $def;
?>
            <div class="form-group row">
                <label for="<?=$id?>" class="col-sm-2 form-control-label form-control-label-sm"><?=$def->label?></label>
                <input type="<?=$def->type?>" class="col-sm-10 form-control form-control-sm" id="<?=$id?>" placeholder="<?=$def->placeholder?>" data-bind="value: data.<?=$id?>">
            </div>
<?php
            }
?>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" data-bind="click: submit, text: buttonLabel, disabled: progress, css: {disabled: progress}">OK</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script>
    function  submit<?=$dialogJSVariable?> (model)
        {
        ajaxCall (model.baseUrl, model.<?=$dialogJSVariable?>.fn (), ko.toJS(model.<?=$dialogJSVariable?>.data),
                  model.<?=$dialogJSVariable?>, model.<?=$dialogJSVariable?>.progress, model.<?=$dialogJSVariable?>.successCallback);
        }
    function  edit<?=$dialogJSVariable?> (model, title, action, buttonText, row, updateRow)
        {
        model.<?=$dialogJSVariable?>.dialogTitle (title);
        model.<?=$dialogJSVariable?>.fn (action);
        model.<?=$dialogJSVariable?>.buttonLabel (buttonText);
        model.<?=$dialogJSVariable?>.successCallback = function (data)
            {
            $('#<?=$dialogId?>').modal('hide'); 
            updateRow(data);
            }
        model.<?=$dialogJSVariable?>.data.id (ko.unwrap(row.id));
        model.<?=$dialogJSVariable?>.errors ([]);
<?php
        foreach ($visibleParams as $id => $def)
            {
?>
            model.<?=$dialogJSVariable?>.data.<?=$id?> (ko.unwrap(row.<?=$id?>));
            model.<?=$dialogJSVariable?>.data.<?=\Chores\Model::PREFIX_OLD_ID?><?=$id?> = ko.unwrap(row.<?=$id?>);
<?php
            }
?>
        $('#<?=$dialogId?>').modal('show');
        }
    function  <?=$initFn?> (model)
        {
        model.<?=$dialogJSVariable?> =
            {
                data: { id : ko.observable(0) },
                dialogTitle : ko.observable('Edit'),
                buttonLabel : ko.observable('OK'),
                submit : function () { submit<?=$dialogJSVariable?>(model); },
                errors : ko.observableArray(),
                progress : ko.observable(false),
                fn: ko.observable('create'),
            };
<?php
        foreach ($visibleParams as $id => $def)
            {
?>
            model.<?=$dialogJSVariable?>.data.<?=$id?> = ko.observable();
<?php
            }
?>
        }
</script>
<?php
        return (object)[ 'id' => $dialogJSVariable, 'initFn' => $initFn, 'editFn' => "edit{$dialogJSVariable}" ];
        }
    }
