<?php
/** ****************************************************************************
 *  MailSpecificationImpl                                    clubMgt project
 * ****************************************************************************
 *
 *   File Description:
 *
 *     Implémentation des comportements par défaut pour l'affichage et
 *      traitement des messages par catégorie.
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
abstract class MailSpecificationImpl implements MailSpecification
{

    protected $name;

    protected $CLUB_LANG;

    protected $selection;

    protected $coaches;

    function __construct()
    {
        $this->CLUB_LANG = LangLoader::get('club_mgt_common', 'clubMgt');
        $this->name = '';
        $this->selection = -1;
        $this->coaches = array();
    }

    /*
     * Donne l'element qui doit etre selectionne par defaut.
     */
    public function setSelection($id)
    {
        $this->selection = $id;
    }

    /*
     * Prepare les donnees qui seront dans la vue tpl
     * @return retourne un tableau associatif nom => donnee
     */
    public function prepareView($active)
    {
        $table = array(
            "tab_name" => $this->CLUB_LANG[$this->name],
            "tab_class" => $active ? "active" : "",
            "tr_display" => $active ? "block" : "none",
            "tr_id" => $this->name,);

        $table["buttons"] = $this->getButtons();
        $tpl = new StringTemplate($this->view);
        $tpl->put_all(
                array(
                    "SEL_VIEW" => $this->getTplView($active),
                    "preselected" => $this->getPreselected(),
                    "name" => $this->getName(),
                    "del_elem" => $this->CLUB_LANG["del_elem"],
                    "reset_list" => $this->CLUB_LANG["reset_list"],));
        $table["view"] = $tpl->render();

        return $table;
    }

    /*
     * Permet d'avoir le nom du type de mail traite par la classe.
     * @return Donne le nom de la specificite
     */
    public function getName()
    {
        return $this->name;
    }

    /*
     * Calcul les donnees necessaires pour completer le message.
     * @return un tableau contenant un tableau associatif avec
     * toutes les info corespondant à la liste de boutons retournée par getButtons.
     */
    public function retreiveValues(HTTPRequestCustom $request)
    {
        return array();
    }

    /*
     * Retourne la liste des adresses e-mail des coach concernés
     * si on ecrit a un groupe ou a un cours
     * Coaches is filled during the retreiveValues process.
     * TODO modify to have the computation of data at construction time.
     */
    public function getCoachesAdresses()
    {
        return $this->coaches;
    }

    /*
     * Récupère le numéro du flux pour archiver le message
     */
    public function getArchiveFlux(): array
    {
        $stream_id = ClubMgtDBService::get_column_value(ClubMgtSetup::$clubMgt_table_evt_type,
                'stream_id', 'WHERE id=:evt', array('evt'=>ClubMgtUserConfig::load()->get_evt_type()));
        return array($stream_id);
    }

    protected function getButton($name, $sep = false)
    {
        $val = array("name" => $this->CLUB_LANG[$name], "value" => $name);
        if ($sep) $val["separate"] = 1;
        return $val;
    }
    public function getPreselected()
    {
        return array();
    }

    abstract protected function getButtons();

    abstract protected function getTplView($active);

    private $view = '<table><tr><td># INCLUDE SEL_VIEW #</td>
				<td style="padding:10px;">
				<button type="button" style="padding:10px;border-radius:50%;" onclick="add_element(\'{name}\');"><i class="fas fa-arrow-alt-circle-right"></i></button>
				</td><td>
<select id="selected_{name}" name="selected_{name}[]" multiple="multiple" style="height:8em; min-width:50px">
# START preselected #
<option value={preselected.id}>{preselected.display}</option>
# END preselected #
</select>
				</td><td style="padding:0px 7px;">
			<button type="button" class="submit" value="{del_elem}" onclick="removeElement(\'{name}\', false);">{del_elem}</button> </p>
			<button type="button" class="submit" value="{reset_list}" onclick="removeElement(\'{name}\', true);">{reset_list}</button>
			</td></tr></table>';
}

?>