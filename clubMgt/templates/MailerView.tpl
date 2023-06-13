<!-- ==========================================================================
 --  MailerView.tpl                                        clubMgt project
 -- ==========================================================================
 --
 --   File Description:
 --
 --     Vue edition et envoie d'un mail
 --
 --
 --
 --
 --
 --
 --
 --
 -- ==========================================================================
 --  (C) 2015 Catherine Burvelle <contact.burvelle@free.fr>
 -- ==========================================================================
 -->

	# INCLUDE message_helper #
        #{resources('clubMgt/club_mgt_common')}
<script src="templates/AjaxPreparation.js"></script>     
<script src="templates/MailerScripts.js"></script>


<form action="index.php?url=/group/mail/" name="attachFile" method="post" class="fieldset_content" 
		id="attachFile" enctype="multipart/form-data">
		<input type="hidden" name="token" value="{TOKEN}">
		<input type="hidden" name="action" value="uploadfile">
</form>

<form action="index.php?url=/group/mail/" name="sendmailform" method="post" class="fieldset_content" 
		id="sendmailform"
		onsubmit="sendMail()">
<input type="hidden" name="token" value="{TOKEN}">


<!-- ==========================================================================
  -- FROM : choix du from entre l'utilisateur en son nom, ou le club.
  -- ========================================================================== -->
<fieldset>
	<legend>{@mail.reply_to} </legend>
	L'adresse utilisée pour les réponses à ces mails sera : {reply_to}
</fieldset>


<!-- ==========================================================================
  -- TO
  -- ========================================================================== -->
<fieldset>
<legend>{@mail.to} </legend>
<!-- ==========================================================================
  -- SELECTIONNE le type de destinataire du publipostage.
  -- 	Suivant cette selection, la div to_content et la liste des contenus
  -- 	btlist sont changes.
  -- ========================================================================== -->
<div id="to_kind_div">
	<input type="hidden" id="to_kind" name="to_kind" value="{to_kind}">
	<ul class="tabulation">
	# START btlist #
		<li class="{btlist.tab_class}" id="li_{btlist.tr_id}" onclick="move_to_tab('{btlist.tr_id}')">
			<a href="#">{btlist.tab_name}</a>
		</li>
	# END btlist #
	</ul>
</div>
<!-- ==========================================================================
  -- ENSEMBLE des div permettant de sélectionner la liste des destinatires
  -- parmis la sorte choisie
  -- ========================================================================== -->
<div id="to_content">

	# START btlist #
	<div class="tabulation" id="to_by_{btlist.tr_id}" style="display: {btlist.tr_display}">
		{btlist.view}
	</div>
	# END btlist #

 	
 <!-- <div class="warning">Les gym a b c doivent etre prevenues par telephone</div>
 -->
 </div>
 </fieldset>


<!-- ==========================================================================
  -- SUJET du message
  -- ========================================================================== -->
<fieldset>
	<legend>{@mail.subject}</legend>
	<input type="text" name="subject" id="subject" size="130" required>
</fieldset>


<!-- ==========================================================================
  -- CORP du message
  -- ========================================================================== -->
<fieldset>
<legend>{@mail.message}</legend>

<div class="form-element form-element-textarea editor-bbcode">
<!-- ==========================================================================
  -- LISTE des donnees disponibles suivant le style de TO choisi
  -- ========================================================================== -->
<table class="bbcode" style="margin-left:auto; margin-right:auto; margin-bottom:10px">

	<tr>
	<td style="width:100%;"><i class="fa fa-paperclip"></i> Joindre une facture : 
	# START factureList #
	<input id="facture_{factureList.id}" type="checkbox" name="facture[]" value="{factureList.id}"> <label for="facture_{factureList.id}" style="margin-right: 5px;">{factureList.name}</label>
	# END factureList #
	</td>
	</tr>
	<tr>
	<td style="width:100%;"><i class="fa fa-paperclip"></i> Joindre un document : <input type="file" name="AttachedFileName" onchange="sendFile();" form="attachFile">

	<span id="conteneur">

	</span>
	</td>
	</tr>
		# START btlist #
	<tr id="row_{btlist.tr_id}"  style="display: {btlist.tr_display}">
		<td style="padding:1px;">
			<img src="{PATH_TO_ROOT}/templates/default/images/form/separate.png" alt="" />
			# START btlist.buttons #
				<button type="button" name="{btlist.buttons.name}" title="{btlist.buttons.value}" 
						onclick='insertbbcode("\{{btlist.buttons.value}\}", "smile", "mail_body")'>{btlist.buttons.name}</button>
				# IF btlist.buttons.separate #
					<img src="{PATH_TO_ROOT}/templates/default/images/form/separate.png" alt="" />
				# ENDIF #
			# END btlist.buttons #
		</td>
	</tr>
	# END btlist #
	
</table>
<div class="form-field form-field-textarea picture-status-constraint field-required bbcode-sidebar">
{USER_EDITOR}
<textarea rows="20" cols="80" id="mail_body" name="mail_body"></textarea>
</div>
</div>
</fieldset>


<!-- ==========================================================================
  -- ENVOIE ou previsualisation du message
  -- ========================================================================== -->
<fieldset class="fieldset_submit">
	<legend>{@mail.send}</legend>
	<button type="button" name="action" id="preview" class="submit" value="preview" onclick="askPreview( this );" >{@mail.preview}</button> <!-- onclick="XMLHttpRequest_preview();"-->
	<button type="submit" name="action" id="send" class="submit" value="send">{@mail.send}</button>
</fieldset>


<!-- ==========================================================================
  -- ZONE de previsualisation du message
  -- ========================================================================== -->
<fieldset>
	<legend>{@mail.preview}</legend>
	<div id='div_preview'>
	</div>
</fieldset>

</form>

<script type="text/javascript">
<!-- 
	var current_tab = document.getElementById("to_kind").value;
	if (current_tab != '{to_kind}')
	{
		document.getElementById("to_kind").value = '{to_kind}';
		move_to_tab(current_tab);
	}
 -->
 </script>