<!-- ==========================================================================
 --  MailerConfirmationView.tpl                               clubMgt project
 -- ==========================================================================
 --
 --   File Description:
 --
 --     Vue confirmation d'envoie d'un mail
 --
 --
 --
 --
 --
 --
 --
 --
 -- ==========================================================================
 --  (C) 2016 Catherine Burvelle <contact.burvelle@free.fr>
 -- ==========================================================================
 -->

	# INCLUDE message_helper #
        #{resources('clubMgt/club_mgt_common')}
<h2>{subject}</h2>
<!--  	// Dernier message envoye -->
<fieldset>
<legend>{@lastemail}</legend>
# INCLUDE LASTEMAIL #
</fieldset>

<!--  	// Corp du message sans les remplacements -->
<fieldset>
<legend>{@bodyemail}</legend>
{body}
</fieldset>

<!--  	// Liste des e-mail auxquels le message a ete envoye -->
<fieldset>
<legend>{@listemail}</legend>
{listto}
</fieldset>

# IF warns #
<!--  	// Liste des personnes qui n'ont pas d'email enregistre. -->	
<fieldset class='warning'>
<legend>{@listwarn}</legend>
<table>
<tr>
<th>{@admin.name}</th>
<th>{@admin.tel}</th>
</tr>
# START warns #
<tr>
<td>{warns.respA_firstname} {warns.respA_name}</td>
<td style="text-align:right;">{warns.respA_tel}</td>
</tr>
# END warns #
</table>
</fieldset>
        
# ENDIF #
        