
	function BuildData( form, button )
	{
		selectList();
		
		var data = "";
		var all_input = form.getElementsByTagName("input");
		var i, elt;
		for (i=0; i < all_input.length; i++)
		{
			elt = all_input[i];
			if (elt.type == "checkbox")
			{
				if ( elt.checked )
					data += "&" + elt.name + "=on";
			}
			else if (elt.type == "radio" )
			{
				if (elt.checked)
					data += "&" + elt.name + "=" + elt.value;
			}
			else if (elt.type == "submit" ) 
			{
				continue;		
			} else {
				data += "&" + elt.name + "=" + elt.value;
			}
		}
		
		var all_select = form.getElementsByTagName("select");
		for (i=0; i < all_select.length; i++)
		{
			elt = all_select[i];
			if (elt.multiple)
			{
				for ( j = 0; j < elt.options.length; j++)
					if (elt.options.item(j).selected)
						data += "&" + elt.name + "=" + elt.options.item(j).value;
			}
			else
				data += "&" + elt.name + "=" + elt.options.item(elt.selectedIndex).value;
		}
		var all_txtA = form.getElementsByTagName("textarea");
		for (i=0; i < all_txtA.length; i++)
		{
			elt = all_txtA[i];
			data += "&" + elt.name + "=" + elt.value;
		}
		
		data += "&" + button.name + "=" + button.value;
		
		return data;
	}
	
	function askPreview(button)
	{
		var form = document.getElementById("sendmailform");
		var data = BuildData(form, button);

		var xhr = xmlhttprequest_init("index.php?url=/group/mail/");
		//On prépare la fonction de traitement du résultat de la requéte
		xhr.onreadystatechange = function() 
		{
			//Transfert terminé avec succés
			if( xhr.readyState == 4 && xhr.status == 200 && xhr.responseText != "")
			{
				document.getElementById("div_preview").innerHTML = xhr.responseText;
			}
			else if(  xhr.readyState == 4 && xhr.responseText == "" ) //Error
			{
				//Echec : impossible de joindre le serveur
			}
			
		}
		//On envoie la requéte
		xmlhttprequest_sender(xhr, data);

		return false;
	}
	
	function move_to_tab( tab_id )
	{
		var current_tab = document.getElementById("to_kind").value;
		if (current_tab != '')
		{
			//alert('|'+current_tab+'|');
			// deactivate old tab 		=> li_<current_tab>
			document.getElementById('li_'+current_tab).className = '';
			
			// hide old panel choice	=> to_by_<current_tab>
			document.getElementById('to_by_'+current_tab).style.display = 'none';
			
			// hide old button row 		=> row_<current_tab>
			document.getElementById('row_'+current_tab).style.display = 'none';

			// disabled the output list	=> selected_<current_tab>
			document.getElementById('selected_'+current_tab).disabled = true;
		}
		
		// activate new tab 		=> li_<tab_id>
		document.getElementById('li_'+tab_id).className = 'active';
		
		// show new panel choice	=> to_by_<tab_id>
		document.getElementById('to_by_'+tab_id).style.display = 'block';
		
		// show new button row		=> row_<tab_id>
		document.getElementById('row_'+tab_id).style.display = 'block';
		
		// set current_tab to tab_id
		document.getElementById("to_kind").value = tab_id;
		
		// enabled the output list	=> selected_<current_tab>
		document.getElementById('selected_'+current_tab).disabled = false;
	}
	
	function add_element( name )
	{
		var orig = document.getElementById('list_'+name);
		var dest = document.getElementById('selected_'+name);
		
		if (orig.tagName == "INPUT")
		{
			var vals = orig.value.split('-');
			move_group(orig.value, vals[0], dest);
		}
		else
			move_groups(orig, dest);
	}
	
	function move_groups(from, to)
	{
		for(var i=from.options.length - 1; i >= 0 ; i--)
		{
			if (from.options[i].selected)
			{
				move_group(from.options.item(i).text, from.options.item(i).value, to);
			}
		}
	}
	
	function move_group(copyText, copyValue, to)
	{
		var c = document.createElement("option");
		c.text = copyText;
		c.value = copyValue;
		to.add(c);
	}

	function selectList(  )
	{
		var name = document.getElementById('to_kind').value;
		var grp_sel = document.getElementById('selected_'+name);
		for(var i=0; i< grp_sel.options.length; i++)
		{
			grp_sel.options[i].selected = true;
		}
		return true;
	}

	function removeElement(name, all)
	{
		var grp_sel = document.getElementById('selected_'+name).options;
		var len = grp_sel.length - 1;
		for(var i=len; i >= 0 ; i--)
		{
			if (all || grp_sel[i].selected)
				grp_sel.remove(i);
		}
	}
	
	function sendMail()
	{
		if ( window.confirm('Envoyer le message ? '))
		{
		 	return selectList(  );
		} else {
			return false;
		}
	}
	
/*	var chooserWindow;
	function openFileChooser(path_to_root)
	{
		chooserWindow = window.open(path_to_root+'/user/upload.php?popup=1&fd=temp_insert&parse=1&no_path=1', 
										'', 'height=550,width=720,resizable=yes,scrollbars=yes');

		chooserWindow.addEventListener("beforeunload", updateAttachFileList);
		return true;
	}

	function updateAttachFileList(event)
	{


		var data = document.getElementById('temp_insert');
		var path = data.value;
		if (path==''){
			return true;
		}
		var model = document.getElementById('fileModel').innerHTML;
		var conteneur = document.getElementById('conteneur');
		
		var splitedPath = path.split('/');
		var filename = splitedPath[splitedPath.length-1];
		
		result=model.replace('%FILEPATH%', path).replace('%FILENAME%', filename);
		
		conteneur.innerHTML += result;
		data.value='';
	}

*/

function sendFile()
{
	var formElement = document.getElementById('attachFile');

	var xhr_object = xmlhttprequest_init("index.php?url=/group/mail/");

	//On prépare la fonction de traitement du résultat de la requéte
	xhr_object.onreadystatechange = function() 
	{
		//Transfert terminé avec succés
		if( xhr_object.readyState == 4 && xhr_object.status == 200 && xhr_object.responseText != "")
		{
			document.getElementById('conteneur').innerHTML += xhr_object.responseText;
		}
		else if(  xhr_object.readyState == 4 && xhr_object.responseText == "" ) //Error
		{
			//Echec : impossible de joindre le serveur
			console.log("Error readyState == 4 && empty responseText");
		} else {
			console.log("Error readyState == "+xhr_object.readyState+ " responseText = " + xhr_object.responseText);

		}
		
	}
	//On envoie la requéte
	xhr_object.send( new FormData(formElement));

}
