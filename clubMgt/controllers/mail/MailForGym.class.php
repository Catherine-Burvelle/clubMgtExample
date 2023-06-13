<?php
/** ****************************************************************************
 *  MailForGym                                    clubMgt project
 * ****************************************************************************
 *
 *   File Description:
 *
 *     Implémentation des comportements pour l'affichage de la messagerie pour
 *      envoyer à un ensemble de gymnastes.
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
class MailForGym extends MailSpecificationImpl
{

    function __construct(array $ids = NULL)
    {
        parent::__construct();
        $this->name = "gym";
        $this->selection = 0;
        $this->gymlist = array();
        if (!empty($ids))
        {
            $this->where = "id IN (" . implode(',', $ids) . ')';
        }
        else
        {
            $this->where = '1';
        }
    }

    protected function getButtons()
    {
        $table = array();
        $table[] = $this->getButton("gym_firstname");
        $table[] = $this->getButton("gym_name");
        $table[] = $this->getButton("birthdate");
        $table[] = $this->getButton("license");
        $table[] = $this->getButton("group_list");
        $table[] = $this->getButton("respA_firstname");
        $table[] = $this->getButton("respA_name");

        return $table;
    }

    protected function getTplView($active)
    {
        $tpl = new StringTemplate($this->GYM_TPL);
        $res = ClubMgtDBService::select(
                "SELECT id, CONCAT(id,'-',firstname,' ',name) AS display FROM " . ClubMgtSetup::$clubMgt_table_gymnaste . ' WHERE ' .
                $this->where . ' ORDER BY firstname ASC');
        while ($g = $res->fetch())
        {
            $this->gymlist[] = $g;
            // Debug::stop($g);
        }

        $tpl->put('gym', $this->gymlist);
        $tpl->put_all(array("name" => $this->getName()));

        return $tpl;
    }

    public function getPreselected()
    {
        return $this->gymlist;
    }
    /*
     * Calcul les donnees necessaires pour completer le message.
     * @return un tableau contenant un tableau associatif avec
     * toutes les info corespondant à la liste de boutons retournée par getButtons.
     */
    public function retreiveValues(HTTPRequestCustom $request)
    {
        // Get user's selected Event Type (year)
        $user_config = ClubMgtUserConfig::load();
        $current_evt = $user_config->get_evt_type();

        $values = array();
        $all_disp = $request->get_postarray('selected_' . $this->getName(), array());
        // $disp = $request->get_poststring('gym_display');
        if (empty($all_disp)) return $values;

        foreach ($all_disp as $id)
        {
            try
            {
                // list ( $id, $name ) = explode ( '-', $disp );

                $allGym = ClubMgtDBService::select(
                        "SELECT r.id AS resp_id, r.respA_email, r.respA_name, r.respA_firstname, r.respA_tel, r.respB_email, r.respB_name, " .
                        "r.respB_firstname, r.respB_tel, g.name AS gym_name, g.firstname AS gym_firstname, g.email AS gym_email," .
                        "g.license, g.birthdate, f.group_id_list" .
                        //f.id AS file_id, f.annotation AS annot".
                        " FROM " . ClubMgtSetup::$clubMgt_table_responsible . " r RIGHT JOIN (" . ClubMgtSetup::$clubMgt_table_gymnaste . " g JOIN " .
                        ClubMgtSetup::$clubMgt_table_file . " f ON f.gym_id=g.id ) ON r.id=f.resp_id WHERE g.id=:gid AND f.evt_type=:etype",
                        array('gid' => $id, 'etype' => $current_evt));
                if (! $gym = $allGym->fetch()) continue;

                $gym['group_list'] = Util::grpIdsToGrpNames($gym['group_id_list'], $isComp, $nbGrp);
                $url = new Url("index.php?url=/family/show/".$gym['resp_id']."&receipt=".$current_evt);
                $gym['receipt'] = '<a href="'.$url->absolute().'">facture</a>';

                $values[] = $gym;
            } catch (Exception $e)
            {}
        }
        return $values;
    }

    private $where;

    private $gymlist;

    private $GYM_TPL = "	<label>Gymnaste : </label><input type='text' list='data_list_{name}' id='list_{name}' size=50>
	<datalist id='data_list_{name}'>
	# START gym #
	<option value='{gym.display}' data='{gym.id}'>
	# END gym #
	</datalist>";
}
?>