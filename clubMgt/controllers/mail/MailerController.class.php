<?php

/** ****************************************************************************
 *  MailerController                                    clubMgt project
 * ****************************************************************************
 *
 *   File Description:
 *
 *     Controlleur permettant d'envoyer des mails par groupes/cours/etc... :
 *
 *
 *
 *
 *
 *
 *
 *
 * ****************************************************************************
 *  (C) 2014-2023 Catherine Burvelle <contact.burvelle@free.fr>
 * ****************************************************************************
 */
class MailerController extends AbstractController
{

    private $tpl, $doPreview;

    private $CLUB_LANG;

    private $clubMgtConfig;

    private $what, $ids;

    private $bypass = false;

    /**
     * Point d'entrée de la requete.
     * (non-PHPdoc)
     *
     * @see AbstractController::execute()
     */
    public function execute(HTTPRequestCustom $request)
    {
        clubMgtAuthorizationsService::check_loggin();
        clubMgtAuthorizationsService::check_authorizations(clubMgtAuthorizationsService::AUTH_GROUP_MAIL_SEND, true);
        $this->init();
        if (!$this->bypass && $request->has_postparameter('action'))
        {
            $action = $request->get_poststring('action');
            if ($action == 'uploadfile')
            {
                // Upload a file that will be attached to the mail
                // This is an AJAX request so response will be without site decoration.
                $this->storeFile($request);
                $this->doPreview = true;
            }
            else
            {
                $this->doPreview = $action != 'send';
                $this->send_mail($request);
            }
        }
        else
        {
            $this->new_mail();
        }
        return $this->build_response($this->tpl);
    }

    /*
     * Initialisation du controlleur
     */
    public function init()
    {
        $this->doPreview = false;
        // Chargement de la langue du module.
        $this->CLUB_LANG = LangLoader::get('club_mgt_common', 'clubMgt');
        $this->clubMgtConfig = ClubMgtConfig::load();
        $this->tpl = new StringTemplate("<div class='warning'>Le mail a un probleme.</div>");
    }

    private function storeFile(HTTPRequestCustom $request): void
    {
        $root_dir = PATH_TO_ROOT . '/cache/attachedfiles/';
        $sub_dir = AppContext::get_current_user()->get_id() . '/';
        if (!is_dir($root_dir . $sub_dir) && !mkdir($root_dir . $sub_dir))
        {
            ErrorHandler::add_error_in_log($root_dir . $sub_dir, 'mkdir fails');
            $this->tpl = new StringTemplate('');
            return;
        }
        $Upload = new Upload($root_dir . $sub_dir);

        $Upload->file('AttachedFileName', '', false, 400000000, false);
        $upload_error = $Upload->get_error();

        if (!empty($upload_error))
        {
            ErrorHandler::add_error_in_log(print_r($Upload, true), 'Upload fails');
            $this->tpl = new StringTemplate('');
            return;
        }

        $model = '<span style="border: solid 1px;border-radius: 10px;padding: 5px;">
        <input type="hidden" name="attachfilename[]" value="' . $Upload->get_filename() .
                    '">
        <button type="button" class="fa fa-trash" name="suppress" onclick="this.parentNode.outerHTML=\'\';"></button> ' .
                    $Upload->get_original_filename() . '</span>';

        $this->tpl = new StringTemplate($model);
    }

    private function send_mail(HTTPRequestCustom $request)
    {
        $ftags = $request->get_postvalue('ftags', TSTRING_UNCHANGE);
        $forbidden_tags = explode(',', $ftags);

        // Get the body and parse it to translate the BBCode into HTML
        $body = $this->parse($request->get_poststring('mail_body'), $forbidden_tags);

        // Get the subject, no substitution inside
        $subject = $request->get_poststring('subject');

        // Get the reply_to value (ancien from)
        $user = AppContext::get_current_user();
        $reply_to = $user->get_email();
        $reply_to_display = $user->get_display_name();

        // Get the kind of mailing to perform, and build the corresponding object.
        $to_kind = $request->get_poststring('to_kind');
        $mail_data = null;
        // Debug::stop($to_kind);
        switch ($to_kind) {
            case 'group':
                $mail_data = new MailForGroup();
                break;
            case 'status':
                $mail_data = new MailForStatus();
                break;
            case 'gym':
                $mail_data = new MailForGym();
                break;
            case 'resp':
                $mail_data = new MailForResponsible();
                break;
            case 'lesson':
                $mail_data = new MailForLesson();
                break;
            case 'role':
                $mail_data = new MailForRole();
                break;
        }
        // Retreive data about mailing gym.
        $allGym = $mail_data->retreiveValues($request);

        if (empty($allGym))
        {
            $this->tpl = new StringTemplate("Pas d'adhérant sélectionné");
            return;
        }

        // List of invoices to attach to the mail
        $invoiceList = $request->get_postarray('facture');
        // List of files to attach to the mail
        $attachFilenameList = $request->get_postarray('attachfilename');
        $attach_dir = PATH_TO_ROOT . '/cache/attachedfiles/' . AppContext::get_current_user()->get_id() . '/';

        $mailling_list = '';
        $warn = array();
        $warnDisplay = "";
        $invoice_list = "Liste des factures jointes : ";
        $msg_tpl = null;
        // Loop on all gym
        foreach ($allGym as $gym)
        {
            // Check the presence of an e-mail
            if (empty($gym['gym_email']) && empty($gym['email']) && empty($gym['respA_email']) && empty($gym['respB_email']))
            {
                // Debug::stop($gym);
                if (!empty($gym['respA_tel']))
                    $gym['respA_tel'] = Util::format_phone_number($gym['respA_tel']);
                else
                    $gym['respA_tel'] = "Pas de No";
                $warn[] = $gym;
                $warnDisplay .= "-- " . $gym['respA_name'] . " : " . $gym['respA_tel'];
                continue;
            }

            // Create the template from the parsed body
            $msg_tpl = new StringTemplate($body);

            // Set specific data of the gym in the template
            $msg_tpl->put_all($gym);

            try
            {
                // Create the mail
                $mail = new Mail();
                $mail->set_is_html(true);
                if (!empty($gym['gym_email']))
                {
                    $mail->add_recipient($gym['gym_email'], Util::format_name($gym['gym_firstname'], $gym['gym_name']));
                    // Update the mailing list string to be saved
                    $mailling_list .= '"' . Util::format_name($gym['gym_firstname'], $gym['gym_name']) . '"&lt;' . $gym['gym_email'] . '&gt;;';
                }
                if (!empty($gym['email']))
                {
                    if (empty($gym['display_name']))
                    {
                        $gym['display_name'] = '';
                    }
                    $mail->add_recipient($gym['email'], $gym['display_name']);
                    // Update the mailing list string to be saved
                    $mailling_list .= '"' . $gym['display_name'] . '"&lt;' . $gym['email'] . '&gt;;';
                }
                if (!empty($gym['respA_email']))
                {
                    $mail->add_recipient($gym['respA_email'], Util::format_name($gym['respA_firstname'], $gym['respA_name']));
                    // Update the mailing list string to be saved
                    $mailling_list .= '"' . Util::format_name($gym['respA_firstname'], $gym['respA_name']) . '"&lt;' . $gym['respA_email'] . '&gt;;';
                }
                if (!empty($gym['respB_email']))
                {
                    $formatedName = Util::format_name(
                            !empty($gym['respB_firstname']) ? $gym['respB_firstname'] : $gym['respA_firstname'],
                            !empty($gym['respB_name']) ? $gym['respB_name'] : $gym['respA_name']);
                    $mail->add_recipient($gym['respB_email'], $formatedName);
                    // Update the mailing list string to be saved
                    $mailling_list .= '"' . $formatedName . '"&lt;' . $gym['respB_email'] . '&gt;;';
                }
                if ($this->doPreview)
                {
                    // If only the preview do not send the mail.
                    continue;
                }

                // Attached Invoice
                if (!empty($invoiceList))
                {
                    $family = new Family($gym['resp_id']);
                    foreach ($invoiceList as $inv_evt_type)
                    {
                        $invoice_list .= $inv_evt_type . ' - ';
                        $invoice = Invoice::getFromFamily($family, $inv_evt_type);
                        if ($invoice == null || empty($invoice->getNumero()))
                        {
                            continue;
                        }
                        $mail->add_string_attachment($invoice->print('S'), "Facture" . $invoice->getNumero() . ".pdf");
                    }
                }

                // Attached Files
                foreach ($attachFilenameList as $filename)
                {
                    $path = $attach_dir . $filename;
                    if (file_exists($path))
                    {
                        $mail->add_file_attachment($filename, $path);
                    }
                }
                $mail->set_subject($subject);
                // Set the content as the complete message as a string
                $mail->set_content($msg_tpl->render());
                $mail->set_reply_to($reply_to, $reply_to_display);
                $this->send_if_allowed($mail);
            } catch (Exception $e)
            { // For any exception catched, continue with next gym.
            }
        }

        // Prepare the confirmation view
        $this->tpl = new FileTemplate('clubMgt/MailerConfirmationView.tpl');
        $values = array();
        $values['LASTEMAIL'] = $msg_tpl; // The last one will be returned
        $values['body'] = $body;
        $values['subject'] = $subject;
        $mailling_list = substr($mailling_list, 0, -1);
        $values['listto'] = $mailling_list;
        $this->tpl->put_all($values);
        $this->tpl->put('warns', $warn);

        if ($this->doPreview)
        {
            return;
        }
        // Store the body and the list of 'to' email
        $mailling_list = htmlentities($mailling_list, ENT_COMPAT, "UTF-8");
        $body = str_replace(array('{', '}'), array('\\\\{', '\\\\}'), $body);
        $flux = $mail_data->getArchiveFlux();
        foreach ($flux as $stream)
        {
            NewsletterDAO::add_archive(
                    $stream,
                    $subject,
                    '<p>' . $mailling_list . '</p> <p>' . $body . '</p>' . $invoice_list . ' <p>' . $warnDisplay . '</p>',
                    NewsletterMailService::HTML_LANGUAGE);
        }

        $coachMails = $mail_data->getCoachesAdresses();

        // Mail to be sent to coach
        $mail = new Mail();
        $mail->set_is_html(true);
        if (empty($coachMails))
        {
            $mail->add_recipient($reply_to, $reply_to_display);
        }
        else
        {
            foreach ($coachMails as $name => $coachEmail)
            {
                $mail->add_recipient($coachEmail, $name);
            }
            $mail->add_cc_recipient($reply_to, $reply_to_display);
        }
        $mail->add_cc_recipient(MailServiceConfig::load()->get_default_mail_sender(), MailServiceConfig::load()->get_default_mail_sender_display());

        $mail->set_subject($subject);
        $mail->set_content(
                "<P>Le message qui suit a été envoyé aux adresses suivantes avec les mots entre accolade remplacés par les info relatives à chaque gymnaste / groupe / cours :</P><P>" .
                $mailling_list . "</P><P>*** MESSAGE ***</P><P>" . $body . "</P>" . $invoice_list . "<p>Personne(s) sans e-mail : " . $warnDisplay .
                '</p>');
        $mail->set_reply_to($reply_to, $reply_to_display);
        // Attached Files
        foreach ($attachFilenameList as $filename)
        {
            $path = $attach_dir . $filename;
            if (file_exists($path))
            {
                $mail->add_file_attachment($filename, $path);
            }
        }
        $this->send_if_allowed($mail);

        // Delete attached files from the directory
        // Attached Files
        foreach ($attachFilenameList as $filename)
        {
            $path = $attach_dir . $filename;
            if (file_exists($path))
            {
                @unlink($path);
            }
        }
        @rmdir($attach_dir);
    }

    public function sendInvoice($resp_id, $evt_type)
    {
        $family = new Family($resp_id);
        $invoice = Invoice::getFromFamily($family, $evt_type);
        if ($invoice == null || empty($invoice->getNumero()))
        {
            return;
        }
        try
        {
            // Create the mail
            $mail = new Mail();
            $mail->add_string_attachment($invoice->print('S'), "Facture" . $invoice->getNumero() . ".pdf");
            $mail->set_is_html(false);
            if (($family->responsibles['respA_facture'] || !$family->responsibles['respB_facture']) && !empty($family->responsibles['respA_email']))
            {
                $mail->add_recipient(
                        $family->responsibles['respA_email'],
                        Util::format_name($family->responsibles['respA_firstname'], $family->responsibles['respA_name']));
            }
            if ($family->responsibles['respB_facture'] && !empty($family->responsibles['respB_email']))
            {
                $formatedName = Util::format_name(
                        !empty($family->responsibles['respB_firstname']) ? $family->responsibles['respB_firstname'] : $family->responsibles['respA_firstname'],
                        !empty($family->responsibles['respB_name']) ? $family->responsibles['respB_name'] : $family->responsibles['respA_name']);
                $mail->add_recipient($family->responsibles['respB_email'], $formatedName);
            }

            $subject = "SPCOC-GR Facture de " . $family->events[$evt_type]['name'];
            $mail->set_subject($subject);
            $mail->set_content(
                    "Bonjour, vous trouverez en pièce jointe votre facture pour votre inscription au SPCOC-GR : " . $family->events[$evt_type]['name'] .
                    "\n Sportivement\n Le Bureau");
            // Get the reply_to value
            $user = AppContext::get_current_user();
            $reply_to = $user->get_email();
            $reply_to_display = $user->get_display_name();
            $mail->set_reply_to($reply_to, $reply_to_display);

            $this->send_if_allowed($mail);
        } catch (Exception $e)
        { // For any exception catched, continue with next gym.
        }
    }

    public function set_mail_view(string $what, array $ids)
    {
        $this->bypass = true;
        $this->what = $what;
        $this->ids = $ids;
    }

    private function new_mail()
    {
        $this->tpl = new FileTemplate('clubMgt/MailerView.tpl');
        $user = AppContext::get_current_user();
        $active_view = "group";

        // BTLIST
        $bt_list = array();
        if ($user->check_level(User::MODERATOR_LEVEL))
        {
            if (!empty($this->what))
            {
                if ($this->what == 'gym')
                {
                    $gym_mail = new MailForGym($this->ids);
                    $bt_list[] = $gym_mail->prepareView(TRUE);
                    $active_view = $gym_mail->getName();
                }
            }
            else
            {
                $grp_mail = new MailForGroup();
                $bt_list[] = $grp_mail->prepareView(TRUE);
                $active_view = $grp_mail->getName();
                $status_mail = new MailForStatus();
                $bt_list[] = $status_mail->prepareView(FALSE);
                $gym_mail = new MailForGym();
                $bt_list[] = $gym_mail->prepareView(FALSE);
                $lesson_mail = new MailForLesson();
                $bt_list[] = $lesson_mail->prepareView(FALSE);
                $resp_mail = new MailForResponsible();
                $bt_list[] = $resp_mail->prepareView(FALSE);
                $bt_list[] = (new MailForRole())->prepareView(FALSE);
            }
        }
        else
        {
            $uc = ClubMgtUserConfig::load();
            $grp_mail = new MailForGroup($uc->get_reading_groups());
            $bt_list[] = $grp_mail->prepareView(TRUE);
            $active_view = $grp_mail->getName();
        }
        $this->tpl->put('btlist', $bt_list);

        // /////
        // USER_EDITOR
        $editor = AppContext::get_content_formatting_service()->get_default_editor(); // $user->get_editor();
        $editor->set_identifier('mail_body');
        $values = array('USER_EDITOR' => $editor->display());
        $values['reply_to'] = '"' . $user->get_display_name() . '"&lt;' . $user->get_email() . '&gt;';
        $values['to_kind'] = $active_view;
        $this->tpl->put_all($values);

        // /////
        // Factures
        $user_config = ClubMgtUserConfig::load();
        $evt_type = $user_config->get_evt_type();

        // Get Stage list and season
        $receipList = array();
        $all_stg = ClubMgtDBService::select_rows(
                ClubMgtSetup::$clubMgt_table_evt_type,
                array('id', 'name'),
                'WHERE id=:mid OR (name LIKE :name_pattern AND master_id=:mid)',
                array('name_pattern' => 'STAGE %', 'mid' => $evt_type));
        while ($an_event = $all_stg->fetch())
        {
            $receipList[] = $an_event;
        }
        $this->tpl->put('factureList', $receipList);
    }

    private function parse($body, $forbidden_tags)
    {

        // PARSER COPIED FROM content_xmlhttprequest.php
        $page_path_to_root = PATH_TO_ROOT;
        $page_path = SCRIPT;

        $editor = ContentFormattingConfig::load()->get_default_editor();

        $contents = stripslashes(htmlentities($body, ENT_COMPAT, "UTF-8"));

        $formatting_factory = AppContext::get_content_formatting_service()->create_factory($editor);

        $parser = $formatting_factory->get_parser();

        if (!empty($forbidden_tags))
        {
            $parser->set_forbidden_tags($forbidden_tags);
        }

        $parser->set_content($contents);
        $parser->set_path_to_root($page_path_to_root);
        $parser->set_page_path($page_path);

        $parser->parse();

        $second_parser = $formatting_factory->get_second_parser();
        $second_parser->set_content($parser->get_content());
        $second_parser->set_path_to_root($page_path_to_root);
        $second_parser->set_page_path($page_path);

        $second_parser->parse();

        $contents = $second_parser->get_content();

        return $contents;
    }

    private function send_if_allowed(Mail $mail)
    {
        $mail->set_sender(MailServiceConfig::load()->get_default_mail_sender(), MailServiceConfig::load()->get_default_mail_sender_display());

        if ($this->clubMgtConfig->get_send_mail() && !$this->doPreview)
        {
            AppContext::get_mail_service()->try_to_send($mail);
        }
    }

    private function build_response(View $view)
    {
        if ($this->doPreview) return new SiteNodisplayResponse($view);

        return new SiteDisplayResponse($view);
    }
}

?>
