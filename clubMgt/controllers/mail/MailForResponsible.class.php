<?php
/** ****************************************************************************
 *  MailForResponsible                                    clubMgt project
 * ****************************************************************************
 *
 *   File Description:
 *
 *     Implémentation des comportements pour l'affichage de la messagerie pour
 *      envoyer à un ensemble de responsables de gym.
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
class MailForResponsible extends MailSpecificationImpl
{

    function __construct()
    {
        parent::__construct();
        $this->name = "resp";
        $this->selection = 0;
    }

    protected function getButtons()
    {
        $table = array();
        $table[] = $this->getButton("respA_firstname");
        $table[] = $this->getButton("respA_name");
        $table[] = $this->getButton("gym_list");
        $table[] = $this->getButton("pseudo");
        $table[] = $this->getButton("receipt");
        return $table;
    }

    protected function getTplView($active)
    {
        $tpl = new StringTemplate($this->RESP_TPL);
        $res = ClubMgtDBService::select(
                "SELECT CONCAT(id,'-',LCASE(respA_firstname),' ',UCASE(respA_name)) AS display FROM " . ClubMgtSetup::$clubMgt_table_responsible .
                ' WHERE 1 ORDER BY respA_firstname ASC');
        while ($g = $res->fetch())
            $this->resplist[] = $g;

        $tpl->put('resp', $this->resplist);
        $tpl->put_all(array("name" => $this->getName()));
        return $tpl;
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

        $all_resp = $request->get_postarray('selected_' . $this->getName(), array());
        if (empty($all_resp)) return array();

        $values = array();
        foreach ($all_resp as $id)
        {
            try
            {
                $resp = ClubMgtDBService::select_single_row(ClubMgtSetup::$clubMgt_table_responsible, array('*', 'id AS resp_id'), "WHERE id=:rid", array('rid' => $id));

                $allGym = ClubMgtDBService::select_rows(
                        ClubMgtSetup::$clubMgt_table_gymnaste,
                        array('name AS gym_name', 'firstname AS gym_firstname', 'email AS gym_email'),
                        "WHERE resp_id=:rid",
                        array('rid' => $id));
                $disp = '';
                while ($gym = $allGym->fetch())
                {
                    $disp .= Util::format_name($gym['gym_firstname'], $gym['gym_name']) . ', ';
                }
                $resp['gym_list'] = substr($disp, 0, -2);
                try
                {
                    $resp['pseudo'] = $resp['user_id'] > 0 ? UserService::get_user($resp['user_id'])->get_display_name() : 'sans_pseudo';
                } catch (Exception $user_e)
                {
                    $resp['pseudo'] = 'no user';
                }
                $url = new Url("index.php?url=/family/show/".$id."&receipt=".$current_evt);
                $resp['receipt'] = '<a href="'.$url->absolute().'">facture</a>';

                $values[] = $resp;
            } catch (Exception $e)
            {
                Debug::dump($e);
            }
        }
        return $values;
    }

    private $resplist;

    private $RESP_TPL = "	<label>Responsable : </label><input type='text' list='data_list_{name}' id='list_{name}' size=50>
	<datalist id='data_list_{name}'>
	# START resp #
		<option value='{resp.display}' >
	# END resp #
	</datalist>";
}
?>