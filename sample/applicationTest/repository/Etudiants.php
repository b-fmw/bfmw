<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace applicationTest\repository;

use bfmw\Application;
use bfmw\repository\AbstractRepository;

/**
 * Repository for student-related queries.
 */
class Etudiants extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(Application::$dataHelpers);
    }


    /**
     * Retrieves distinct students for a given department and academic year.
     *
     * @param int $dept_id  Department identifier.
     * @param int $annee_id Academic year identifier.
     * @return array List of students (id, last name, first name), ordered by last name.
     */
    public function getEtudiantsDuDepartement(int $dept_id,int $annee_id) : array
    {
        $sql = "SELECT DISTINCT ET_ID,ET_NOM_DE_FAMILLE,ET_PRENOM FROM common_etudiant
                    JOIN common_inscription ON (ET_ID = INSCRIPT_ET_ID AND INSCRIPT_ANNEE_ID=$annee_id)
                    JOIN common_semestre ON (SEM_ID = INSCRIPT_SEM_ID)
                    JOIN common_formation ON (FORM_ID = SEM_FORM_ID)
                WHERE FORM_DEPT_ID=$dept_id
                ORDER BY ET_NOM";
        return $this->connector->getData($sql);
    }

}
