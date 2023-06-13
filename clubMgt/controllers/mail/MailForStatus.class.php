<?php
/** ****************************************************************************
 *  MailForStatus                                    clubMgt project
 * ****************************************************************************
 *
 *   File Description:
 *
 *     Implémentation des comportements pour l'affichage de la messagerie pour
 *      envoyer à un ensemble de personnes par rapport à leur status.
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
class MailForStatus extends MailForGym
{

    function __construct()
    {
        parent::__construct();
        $this->name = "status";
        $this->selection = STATUS_VAL;
    }

    protected function getButtons()
    {
        $table = parent::getButtons();
        $table[] = $this->getButton('pseudo');
        $table[] = $this->getButton("receipt");
        $table[] = $this->getButton("inscription_fiche");
        return $table;
    }

    public function retreiveValues(HTTPRequestCustom $request)
    {
        // Get user's selected Event Type (year)
        $user_config = ClubMgtUserConfig::load();
        $current_evt = $user_config->get_evt_type();

        $values = array();
        // $val = $request->get_poststring('status');
        $all_val = $request->get_postarray('selected_' . $this->getName(), array());
        if (empty($all_val)) return $values;

        foreach ($all_val as $val)
        {
            try
            {
                $allGym = ClubMgtDBService::select(
                        "SELECT " . ClubMgtSetup::$clubMgt_table_responsible . ".id AS resp_id, "
                        . ClubMgtSetup::$clubMgt_table_responsible . ".user_id AS respA_uid, " . ClubMgtSetup::$clubMgt_table_responsible .
                        ".respA_email, " . ClubMgtSetup::$clubMgt_table_responsible . ".respA_name, " . ClubMgtSetup::$clubMgt_table_responsible .
                        ".respA_firstname, " . ClubMgtSetup::$clubMgt_table_responsible . ".respA_tel, " . ClubMgtSetup::$clubMgt_table_responsible .
                        ".respB_uid, " . ClubMgtSetup::$clubMgt_table_responsible . ".respB_email, " . ClubMgtSetup::$clubMgt_table_responsible .
                        ".respB_name, " . ClubMgtSetup::$clubMgt_table_responsible . ".respB_firstname, " . ClubMgtSetup::$clubMgt_table_responsible .
                        ".respB_tel, " . ClubMgtSetup::$clubMgt_table_gymnaste . ".name AS gym_name," . ClubMgtSetup::$clubMgt_table_gymnaste .
                        ".firstname AS gym_firstname," . ClubMgtSetup::$clubMgt_table_gymnaste . ".email AS gym_email," .
                        ClubMgtSetup::$clubMgt_table_gymnaste . ".license," . ClubMgtSetup::$clubMgt_table_gymnaste . ".birthdate," .
                        ClubMgtSetup::$clubMgt_table_file . ".group_id_list" .
                        // ClubMgtSetup::$clubMgt_table_file.".id AS
                        // file_id,".
                        // ClubMgtSetup::$clubMgt_table_file.".annotation
                        // AS annot".
                        "  FROM " . ClubMgtSetup::$clubMgt_table_responsible . " RIGHT JOIN (" . ClubMgtSetup::$clubMgt_table_gymnaste . " JOIN " .
                        ClubMgtSetup::$clubMgt_table_file . " ON " . ClubMgtSetup::$clubMgt_table_file . ".gym_id=" .
                        ClubMgtSetup::$clubMgt_table_gymnaste . ".id ) " . " ON " . ClubMgtSetup::$clubMgt_table_responsible . ".id=" .
                        ClubMgtSetup::$clubMgt_table_file . ".resp_id" . " WHERE " . ClubMgtSetup::$clubMgt_table_file . ".evt_type=:etype" . " AND " .
                        ClubMgtSetup::$clubMgt_table_file . ".validated=:val",
                        array('etype' => $current_evt, 'val' => $val));
                while ($gym = $allGym->fetch())
                {
                    // Remplacement des numéros de groupe par leur nom
                    $grplst = str_replace(';', ',', trim($gym['group_id_list'], ';'));
                    $allGrp = ClubMgtDBService::select_rows(
                            ClubMgtSetup::$clubMgt_table_group,
                            array('grp_name'),
                            'WHERE id IN (:list)',
                            array('list' => $grplst));

                    $displayGroup = '';
                    while ($grp = $allGrp->fetch())
                        $displayGroup .= $grp['grp_name'] . ', ';

                    $gym['group_list'] = substr($displayGroup, 0, -2);

                    // Récupération du login des responsables si un existe.
                    try
                    {
                        if (!empty($gym['respA_uid']))
                        {
                            $gym['pseudo'] = ClubMgtDBService::get_column_value(
                                    DB_TABLE_INTERNAL_AUTHENTICATION,
                                    'login',
                                    "WHERE user_id=:uid",
                                    array("uid" => $gym['respA_uid']));
                        }
                        elseif (!empty($gym['respB_uid']))
                        {
                            $gym['pseudo'] = ClubMgtDBService::get_column_value(
                                    DB_TABLE_INTERNAL_AUTHENTICATION,
                                    'login',
                                    "WHERE user_id=:uid",
                                    array("uid" => $gym['respB_uid']));
                        }
                        else
                        {
                            $gym['pseudo'] = '"Compte à créer"';
                        }
                    } catch (Throwable $e)
                    {
                        $gym['pseudo'] = '"Compte à créer"';
                    }
                    // URL vers la facture
                    $url = new Url("index.php?url=/family/show/".$gym['resp_id']."&receipt=".$current_evt);
                    $gym['receipt'] = '<a href="'.$url->absolute().'">facture</a>';
                    // URL vers la fiche d'inscription
                    $url = new Url("index.php?url=/family/show/".$gym['resp_id']."&print=Imprimer");
                    $gym['inscription_fiche'] = '<a href="'.$url->absolute().'">fiche d\'inscription</a>';

                    $values[] = $gym;
                }
            } catch (Exception $ex)
            {}
        }
        return $values;
    }

    protected function getTplView($active)
    {
        $tpl = new StringTemplate($this->STATUS_TPL);
        $status = array();
        for ($i = 0; $i < 5; $i++)
        {
            $status[] = array('id' => $i, 'name' => $this->CLUB_LANG['etat.' . $i], 'selected' => $i == $this->selection ? 'selected' : '');
        }
        $tpl->put('status', $status);
        $tpl->put_all(array("name" => $this->getName()));

        return $tpl;
    }

    private $STATUS_TPL = " 		<select id='list_{name}'>
 		# START status #
 			<option value={status.id} {status.selected}>{status.name}</option>
 		# END status #
 		</select>";
}
?>