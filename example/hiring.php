<?php 
/**
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @link https://github.com/EFTEC/StateMachineOne
 */

use eftec\statemachineone\Flags;

use eftec\statemachineone\Job;
use eftec\statemachineone\StateMachineOne;
use mapache_commons\Debug;

$stateMachine=new StateMachineOne(null);
$stateMachine->setDebug(true);
$stateMachine->setDebugAsArray(true);

$stateMachine->setDefaultInitState(IdStatusSTOP);

$stateMachine->setStates([
    IdStatusSTOP=>'Detenido',
    IdStatusPREINJECT1=>'Pre Inyeccion',
    IdStatusPREINJECT2=>'Pre Inyeccion',
    IdStatusINJECT1=>'Inicio Inyeccion',
    IdStatusINJECTEND=>'Fin Inyeccion',
    IdStatusPREINIT=>'Revisando Peso',
    IdStatusINIT=>'Proceso Iniciado',
    IdStatusNOFAN=>'Proceso Iniciado (sin fan)',
    IdStatusREINJECT=>'Reinyectar',
    IdStatusENDPROCESS=>'Fin del Proceso',
    IdStatusENDEVACUATION=>'Fin de evacuación',
    IdStatusTIMEOUT=>'Fin del tiempo'
],false);

$stateMachine->fieldDefault=['V1'=>1];

$stateMachine->addTransition('PREHIRING','INTERVIEW'
        ,'when CANDIDATE');



$stateMachine->viewUI(null,$msg); // null means it takes the current job