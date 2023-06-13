<!-- ==========================================================================
 --  MailForGroupView.tpl                                      clubMgt project
 -- ==========================================================================
 --
 --   File Description:
 --
 --     Vue du panel de selection d'un groupe pour le mailer.
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
        #{resources('clubMgt/club_mgt_common')}
        
		<label>{@clubMgt.group.desc}</label>
		<select id="list_{name}" name="list_{name}" >
			<optgroup label={@grp_kind.loisir}>
			# START group1 #
				<option id="grp_{group1.id}" value={group1.id}  # IF group1.sel # selected # ENDIF # >{group1.grp_name}</option>
			# END group1 #
			</optgroup>
			<optgroup label={@grp_kind.compet}>
			# START group2 #
				<option  id="grp_{group2.id}" value={group2.id}  # IF group2.sel # selected # ENDIF # >{group2.grp_name}</option>
			# END group2 #
			</optgroup>
			<optgroup label={@grp_kind.adult}>
			# START group3 #
				<option  id="grp_{group3.id}" value={group3.id}  # IF group3.sel # selected # ENDIF # >{group3.grp_name}</option>
			# END group3 #
			</optgroup>
		</select>			
