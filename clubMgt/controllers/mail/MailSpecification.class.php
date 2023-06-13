<?php
/** ****************************************************************************
 *  MailSpecification                                    clubMgt project
 * ****************************************************************************
 *
 *   File Description:
 *
 *     Interface pour gérer les donnees à afficher dans l'editeur de mail
 *      suivant la catégorie de destinataire du message
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
interface MailSpecification
{

    /*
     * Prepare les donnees qui seront dans la vue tpl
     * @return retourne un tableau associatif nom => donnee
     */
    public function prepareView($active);

    /*
     * Permet d'avoir le nom du type de mail traite par la classe.
     * @return Donne le nom de la specificite
     */
    public function getName();

    /*
     * Calcul les donnees necessaires pour completer le message
     * @return un tableau contenant un tableau associatif avec
     * toutes les info necessaires pour chaque message.
     */
    public function retreiveValues(HTTPRequestCustom $request);

    /*
     * Donne l'element qui doit etre selectionne par defaut.
     */
    public function setSelection($id);

    /*
     * Retourne la liste des adresses e-mail des coach concernés
     * si on ecrit a un groupe ou a un cours
     */
    public function getCoachesAdresses();

    /*
     * Récupère le numéro du flux pour archiver le message
     */
    public function getArchiveFlux(): array;

    /*
     * Retourne la liste des entité a mettre en preselection
     */
    public function getPreselected();
}

?>