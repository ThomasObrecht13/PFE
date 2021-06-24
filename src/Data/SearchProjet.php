<?php

namespace App\Data;

use Doctrine\ORM\Mapping as ORM;

class SearchProjet
{
    /*
     * @var String
     */
    public $sujet = '';

    /*
     * @var String
     */
    public $nomEtudiant = '';

    /*
     * @var String
     */
    public $nomTuteur = '';

    /*
     * @var String
     */
    public $date = '';
}