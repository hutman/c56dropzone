<?php
defined('C5_EXECUTE') or die("Access Denied.");

Loader::library('file/types');

$u = new User();
$ch = Loader::helper('concrete/file');
$h = Loader::helper('concrete/interface');
$fh = Loader::helper('validation/file');
$form = Loader::helper('form');
$valt = Loader::helper('validation/token');

$fp = FilePermissions::getGlobal();
if (!$fp->canAddFiles()) {
	die(t("Unable to add files."));
}

$types = $fp->getAllowedFileExtensions();
$searchInstance = Loader::helper('text')->entities($_REQUEST['searchInstance']);
$ocID = 0;
if (Loader::helper('validation/numbers')->integer($_REQUEST['ocID'])) {
	$ocID = $_REQUEST['ocID'];
}

$types = $ch->serializeUploadFileExtensions($types);

$umf = ini_get('upload_max_filesize');
$umf = str_ireplace(array('M', 'K', 'G'), array(' MB', 'KB', ' GB'), $umf);

$incoming_contents = $ch->getIncomingDirectoryContents();
?>
<script src="/js/dropzone.js"></script>
<link rel="stylesheet" href="/css/dropzone.css">

<div style="display: none" id="dialog-buttons-multiple">
	<?php
		print $h->button_js(t('Edit Properties'), 'finishUploads();', 'right', 'finish-btn primary', array('disabled' => true, 'class' => 'finish-btn'));
		print $h->button_js(t('Cancel'), 'jQuery.fn.dialog.closeTop();', 'left', null);
	?>
</div>

<div style="display: none" id="dialog-buttons-incoming">
	<?php
		print $form->submit('submit', t('Import Files'), array('onclick' => "jQuery.fn.dialog.showLoader();$('#ccm-file-manager-multiple-incoming').submit()", 'class' => 'primary ccm-button-right'));
	?>
</div>

<div id="dialog-buttons-remote" style="display: none">
	<?php
		print $form->submit('submit', t('Import Files'), array('onclick' => "jQuery.fn.dialog.showLoader();$('#ccm-file-manager-multiple-remote').submit()", 'class' => 'primary ccm-button-right'));
	?>
</div>


<div id="multiple-file-wrapper" class="ccm-ui">
	<ul class="tabs" id="ccm-file-import-tabs">
		<li class="active"><a href="javascript:void(0)" id="ccm-file-add-multiple"><?php echo t('Upload Multiple')?></a></li>
		<li><a href="javascript:void(0)" id="ccm-file-add-incoming"><?php echo t('Add Incoming')?></a></li>
		<li><a href="javascript:void(0)" id="ccm-file-add-remote"><?php echo t('Add Remote Files')?></a></li>
	</ul>
	<div id="ccm-file-add-multiple-tab">
		<div class="header">
			<div style="float: right">
				<div class="help-block" style="margin-top: 11px">
					<?php echo t('Upload Max: %s.', ini_get('upload_max_filesize'))?>
					<?php echo t('Post Max: %s', ini_get('post_max_size'))?>
				</div>
			</div>
			<h3><?php echo t('Upload Multiple Files')?></h3>
		</div>
		<div id="dropzone" class="dropzone"></div>
		<div class="dialog-buttons">
			<?php
			print $h->button_js(t('Edit Properties'), 'finishUploads();', 'right', 'finish-btn primary', array('disabled' => true));
			print $h->button_js(t('Cancel'), 'jQuery.fn.dialog.closeTop();', 'left', null);
			?>
		</div>
	</div>
	<div id="ccm-file-add-incoming-tab" style="display: none">
		<h3><?php echo t('Add from Incoming Directory')?></h3>
		<?php if(!empty($incoming_contents)) { ?>
			<form id="ccm-file-manager-multiple-incoming" method="post" action="<?php echo REL_DIR_FILES_TOOLS_REQUIRED?>/files/importers/incoming">
				<input type="hidden" name="searchInstance" value="<?php echo $searchInstance?>" />
				<input type="hidden" name="ocID" value="<?php echo $ocID?>" />
					<table id="incoming_file_table" class="table table-bordered" width="100%" cellpadding="0" cellspacing="0">
						<tr>
							<th width="10%" valign="middle" class="center theader"><input type="checkbox" id="check_all_imports" name="check_all_imports" onclick="ccm_alSelectMultipleIncomingFiles(this);" value="" /></th>
							<th width="20%" valign="middle" class="center theader"></th>
							<th width="45%" valign="middle" class="theader"><?php echo t('Filename')?></th>
							<th width="25%" valign="middle" class="center theader"><?php echo t('Size')?></th>
						</tr>
						<?php
						foreach($incoming_contents as $filenum=>$file_array) {
							$ft = FileTypeList::getType($file_array['name']);
							?>
							<tr>
								<td width="10%" valign="middle" class="center">
									<?php if($fh->extension($file_array['name'])) { ?>
										<input type="checkbox" name="send_file<?php echo $filenum?>" class="ccm-file-select-incoming" value="<?php echo $file_array['name']?>" />
									<?php } ?>
								</td>
								<td width="20%" valign="middle" class="center"><?php echo $ft->getThumbnail(1)?></td>
								<td width="45%" valign="middle"><?php echo $file_array['name']?></td>
								<td width="25%" valign="middle" class="center"><?php echo Loader::helper('number')->formatSize($file_array['size'], 'KB')?></td>
							</tr>
						<?php } ?>
					</table>
				<input type="checkbox" name="removeFilesAfterPost" value="1" />
				<?php echo t('Remove files from incoming/ directory.')?>
				<?php echo $valt->output('import_incoming');?>
			</form>
		<?php } else { ?>
			<?php echo t('No files found in %s', DIR_FILES_INCOMING)?>
		<?php } ?>
	</div>
	<div id="ccm-file-add-remote-tab" style="display: none">
		<h3><?php echo t('Add From Remote URL')?></h3>
		<form method="POST" id="ccm-file-manager-multiple-remote" action="<?php echo REL_DIR_FILES_TOOLS_REQUIRED?>/files/importers/remote">
			<input type="hidden" name="searchInstance" value="<?php echo $searchInstance?>" />
			<input type="hidden" name="ocID" value="<?php echo $ocID?>" />
			<p><?php echo t('Enter URL to valid file(s)')?></p>
			<?php echo $valt->output('import_remote');?>
			<?php echo $form->text('url_upload_1', array('style' => 'width:98%'))?><br/><br/>
			<?php echo $form->text('url_upload_2', array('style' => 'width:98%'))?><br/><br/>
			<?php echo $form->text('url_upload_3', array('style' => 'width:98%'))?><br/><br/>
			<?php echo $form->text('url_upload_4', array('style' => 'width:98%'))?><br/><br/>
			<?php echo $form->text('url_upload_5', array('style' => 'width:98%'))?><br/>
		</form>
	</div>
</div>

<style type="text/css">
	#multiple-file-wrapper {
		display: block;
		height: 100%;

		display: -webkit-box;
		display: -ms-flexbox;
		display: -webkit-flex;
		display: flex;
		flex-direction: column;
	}
	#ccm-file-add-multiple-tab {
		flex-grow: 1;
		display: -webkit-box;
		display: -ms-flexbox;
		display: -webkit-flex;
		display: flex;
		flex-direction: column;
	}
	.dropzone {
		flex-grow: 1;
		width: 100%;
		overflow: auto;
		padding: 10px;
	}
	.dropzone .dz-preview {
		min-height: 80px;
		margin: 3px;
	}
	.dropzone .dz-preview .dz-image {
		width: 80px;
		height: 80px;
	}
	.dropzone .dz-preview .dz-details {
		padding: 0;
	}
	.dropzone .dz-preview .dz-details .dz-size {
		margin-bottom: 5px;
	}
	.dropzone .dz-preview .dz-details .dz-filename span,
	.dropzone .dz-preview .dz-details .dz-size span {
		padding: 0;
	}
</style>

<script type="text/javascript">
	var ccm_fiActiveTab = "ccm-file-add-multiple";
	$("#ccm-file-import-tabs a").click(function() {
		$("li.active").removeClass('active');
		var activesection = ccm_fiActiveTab.substring(13);
		var wind = $(this).parentsUntil('.ui-dialog').parent();
		var bp = wind.find('.ui-dialog-buttonpane');
		$("#dialog-buttons-" + activesection).html(bp.html());

		$("#" + ccm_fiActiveTab + "-tab").hide();
		ccm_fiActiveTab = $(this).attr('id');
		if (ccm_fiActiveTab != 'ccm-file-add-multiple') {
			$('#ccm-file-add-multiple-outer').css('visibility', 'hidden');
		} else {
			$('#ccm-file-add-multiple-outer').css('visibility', 'visible');
		}

		$(this).parent().addClass("active");
		$("#" + ccm_fiActiveTab + "-tab").show();

		var section = $(this).attr('id').substring(13);
		var buttons = $("#dialog-buttons-" + section);
		bp.html(buttons.html());
	});

	Dropzone.autoDiscover = false;
	var swfu = new Dropzone("#dropzone", {
		hiddenInputContainer: '#ccm-file-add-multiple-tab',
		url: '<?php echo REL_DIR_FILES_TOOLS_REQUIRED?>/files/importers/multiple',
		paramName: 'Filedata', // The name that will be used to transfer the file
		maxFilesize: '<?php echo $umf?>', // MB
		maxFiles: 100,
		acceptedFiles: '<?php echo str_replace(';', ',', str_replace('*', '', $types)); ?>',
		params: {'ccm-session' : "<?php echo session_id(); ?>",'searchInstance': '<?php echo $searchInstance?>', 'ocID' : '<?php echo $ocID?>', 'ccm_token' : '<?php echo $valt->generate("upload")?>'},
		thumbnailWidth: 80,
		thumbnailHeight: 80,
		clickable: true,
		init: function() {
			this.on("addedfile", function(file, serverData) {
				$('.finish-btn').prop('disabled', true);
			});
			this.on("success", function(file, response) {
				var serverData = JSON.parse(response);
				ccm_uploadedFiles.push(serverData['id']);
				$('.finish-btn').prop('disabled', true);
			});
			this.on("queuecomplete", function(file) {
				$('.finish-btn').prop('disabled', false);
			});
		},
	});

	function finishUploads() {
		jQuery.fn.dialog.closeTop();
		setTimeout(function() {
			ccm_filesUploadedDialog('<?php echo $searchInstance; ?>');
		}, 100);
	}

	$(function() {
		$("#ccm-file-manager-multiple-remote").submit(function() {
			$(this).attr('target', ccm_alProcessorTarget);
		});

		$("#ccm-file-manager-multiple-incoming").submit(function() {
			$(this).attr('target', ccm_alProcessorTarget);
		});
	});
</script>
