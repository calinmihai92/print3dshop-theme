<?php
/**
 * Conținutul serviciilor. Cheia = slug-ul existent al paginii (URL-urile nu se schimbă).
 * Gravura laser e scoasă momentan (vezi p3d_hidden_services()).
 */

defined( 'ABSPATH' ) || exit;

function p3d_hidden_services() {
	return array( 'gravura-laser-bucuresti' );
}

function p3d_services() {
	return array(

		'support' => array(
			'label'    => 'Service imprimante 3D',
			'icon'     => 'wrench',
			'eyebrow'  => 'Service imprimante 3D · București',
			'h1a'      => 'Service și reparații',
			'h1b'      => 'imprimante 3D',
			'lead'     => 'Imprimanta nu mai printează cum trebuie? O verificăm, o reparăm și o reglăm, ca să printeze din nou precis și fiabil. Întreținere și suport tehnic specializat pentru cele mai populare imprimante 3D.',
			'card'     => 'Diagnoză, reparații, piese de schimb și întreținere pentru cele mai populare imprimante 3D.',
			'points'   => array( 'Diagnoză și evaluare', 'Piese de schimb și reparații', 'Întreținere preventivă' ),
			'brands'   => array( 'Bambu Lab', 'Prusa', 'Creality', 'Anycubic', 'Elegoo', 'Voron', 'și alte mărci' ),
			'problems' => array(
				array( 'Nu mai extrudă', 'Duză înfundată, extruder care sare sau filament care nu mai iese.' ),
				array( 'Primul strat nu se prinde', 'Piesele se dezlipesc de pe pat sau se deformează la colțuri.' ),
				array( 'Straturi decalate, zgomote', 'Axe care sar, curele slăbite, mișcări neregulate.' ),
				array( 'Erori de temperatură', 'Hotend sau pat care nu încălzesc, erori de senzor.' ),
				array( 'Calitate slabă', 'Fire, bule, suprafețe neuniforme, detalii pierdute.' ),
				array( 'Imprimantă nouă, nereglată', 'Montaj, calibrare și primele printări fără bătăi de cap.' ),
			),
			'whatTitle' => array( 'Ce facem ', 'în service' ),
			'what'     => array(
				array( 'search', 'Diagnoză și evaluare', 'Verificăm starea imprimantei și găsim cauza problemei.' ),
				array( 'wrench', 'Înlocuire piese și reparații', 'Schimbăm componentele uzate sau defecte și readucem imprimanta la performanța optimă.' ),
				array( 'shield', 'Întreținere preventivă', 'Curățare, ungere, strângeri și reglaje, ca să previi problemele înainte să apară.' ),
				array( 'chat', 'Asistență tehnică și consultanță', 'Te ajutăm cu setări, materiale și sfaturi de folosire.' ),
				array( 'cycle', 'Actualizări și optimizare', 'Actualizăm și optimizăm imprimanta pentru funcții noi și rezultate mai bune.' ),
			),
			'stepsTitle' => array( 'Cum ', 'funcționează' ),
			'steps'    => array(
				array( 'Ne suni sau ne scrii', 'Spune-ne ce face imprimanta. Pe WhatsApp poți trimite și poze sau un video scurt.' ),
				array( 'Aduci imprimanta', 'Ne găsești în București, pe Șoseaua Iancului nr. 53.' ),
				array( 'Diagnoză', 'Verificăm imprimanta și îți spunem ce trebuie făcut.' ),
				array( 'Reparăm și reglăm', 'Înlocuim ce e nevoie, calibrăm și imprimanta e gata de lucru.' ),
			),
			'faq'      => array(
				array( 'Ce mărci de imprimante 3D reparați?', 'Lucrăm cu cele mai populare imprimante 3D, printre care Bambu Lab, Prusa, Creality, Anycubic, Elegoo și Voron, dar și alte mărci. Dacă nu ești sigur, sună-ne sau scrie-ne modelul imprimantei.' ),
				array( 'Pot trimite întâi poze sau un video cu problema?', 'Da. Scrie-ne pe WhatsApp cu o descriere și, dacă poți, poze sau un video scurt. Ne ajută să înțelegem problema mai repede.' ),
				array( 'Faceți și întreținere preventivă?', 'Da. Întreținerea preventivă (curățare, reglaje, verificarea pieselor uzate) ajută imprimanta să funcționeze fiabil și previne defectele.' ),
				array( 'Puteți actualiza sau optimiza imprimanta?', 'Da, facem actualizări și optimizări, ca imprimanta să profite de funcții noi și să printeze mai bine.' ),
			),
			'wa'       => 'Bună ziua! Am o problemă cu imprimanta 3D: ',
			'related'  => array( 'printare-3d-personalizata', 'prototyping' ),
		),

		'printare-3d-personalizata' => array(
			'label'    => 'Printare 3D',
			'icon'     => 'layers',
			'eyebrow'  => 'Printare 3D la comandă · București',
			'h1a'      => 'Printare 3D',
			'h1b'      => 'personalizată',
			'lead'     => 'Perfect pentru proiecte unice, prototipuri sau piese personalizate. Transformăm ideile tale în obiecte reale, cu precizie și atenție la detalii, indiferent de complexitatea designului.',
			'card'     => 'Prototipuri, piese de schimb, obiecte unicat. Trimiți modelul, alegi culoarea, primești piesele.',
			'points'   => array( 'Diverse materiale și culori', 'Prototipuri și piese unicat', 'Ridicare sau livrare' ),
			'whatTitle' => array( 'Pentru ', 'ce printăm' ),
			'what'     => array(
				array( 'cube', 'Prototipuri', 'Testezi forma și funcția unui produs înainte de producție.' ),
				array( 'gear', 'Piese de schimb', 'Refacem piese care nu se mai găsesc sau care s-au rupt.' ),
				array( 'palette', 'Obiecte personalizate', 'Cadouri, decoruri și accesorii unicat, în culorile alese de tine.' ),
				array( 'users', 'Pentru creatori', 'Pentru inovatori, artiști și pasionați de tehnologie.' ),
			),
			'stepsTitle' => array( 'Procesul ', 'pas cu pas' ),
			'steps'    => array(
				array( 'Consultare inițială', 'Discutăm ce îți dorești: aduci o piesă existentă sau ne descrii ideea.' ),
				array( 'Modelul 3D', 'Dacă nu ai model, îl creăm noi, optimizat pentru printare.' ),
				array( 'Aprobare', 'Vezi designul și cerem ajustările dorite înainte de producție.' ),
				array( 'Printare 3D', 'Alegi materialul și culoarea, iar noi printăm cu tehnologie modernă.' ),
				array( 'Finisare și livrare', 'Finisăm piesele și le ambalăm cu grijă: ridicare personală sau livrare.' ),
			),
			'faq'      => array(
				array( 'Ce trebuie să trimit pentru printare?', 'Ideal, modelul 3D al piesei. Dacă nu ai model, poți aduce piesa existentă sau ne poți descrie ideea, iar noi o proiectăm.' ),
				array( 'Pot alege materialul și culoarea?', 'Da. Alegi materialul, culoarea și dimensiunile potrivite nevoilor tale.' ),
				array( 'Cum primesc piesele?', 'Poți alege ridicarea personală sau livrarea la domiciliu.' ),
				array( 'Printați și piese tehnice sau doar decorative?', 'Printăm atât prototipuri și piese funcționale, cât și obiecte decorative sau personalizate.' ),
			),
			'wa'       => 'Bună ziua! Aș vrea o printare 3D: ',
			'related'  => array( 'prototyping', 'support' ),
		),

		'prototyping' => array(
			'label'    => 'Proiectare 3D',
			'icon'     => 'pen',
			'eyebrow'  => 'Proiectare și modelare 3D',
			'h1a'      => 'Proiectare 3D:',
			'h1b'      => 'dă formă ideilor tale',
			'lead'     => 'Pornim de la ideea sau schița ta și creăm modelul 3D, gata de printat. Dezvoltăm concepte inovatoare, pas cu pas, cu software specializat.',
			'card'     => 'De la schiță la model 3D gata de printat. Ideal când nu ai încă fișierul piesei.',
			'points'   => array( 'De la schiță la model', 'Optimizat pentru printare', 'Printare opțională' ),
			'whatTitle' => array( 'De ce ', 'să lucrezi cu noi' ),
			'what'     => array(
				array( 'zap', 'Expertiză tehnică', 'Folosim tehnologii și software actuale pentru modelare 3D.' ),
				array( 'palette', 'Personalizare și flexibilitate', 'Ne adaptăm nevoilor tale și ajustăm designul până e cum îl vrei.' ),
				array( 'clock', 'Livrare eficientă', 'Lucrăm rapid, fără compromisuri la calitate.' ),
				array( 'chat', 'Suport continuu', 'Suntem alături de tine pe tot parcursul proiectului.' ),
			),
			'stepsTitle' => array( 'Cum ', 'lucrăm' ),
			'steps'    => array(
				array( 'Consultare', 'Înțelegem viziunea și cerințele tale.' ),
				array( 'Schiță', 'Pornim de la o schiță, baza modelului 3D.' ),
				array( 'Modelare 3D', 'Creăm modelul în software specializat.' ),
				array( 'Revizuire și aprobare', 'Vezi modelul și facem ajustările necesare.' ),
				array( 'Pregătire pentru printare', 'Optimizăm modelul ca să se printeze corect.' ),
				array( 'Printare (opțional)', 'Dacă dorești, îl printăm pe echipamentele noastre.' ),
				array( 'Livrare', 'Primești modelul pe email sau piesa printată, la ridicare ori prin curier.' ),
			),
			'faq'      => array(
				array( 'Pot porni doar de la o idee sau o schiță?', 'Da. Pornim de la ideea sau schița ta, iar modelul 3D îl creăm noi.' ),
				array( 'Primesc fișierul 3D?', 'Da, modelul îți poate fi livrat pe email. Dacă vrei, îl și printăm.' ),
				array( 'Pot cere modificări?', 'Da. Înainte de finalizare revizuiești modelul și facem ajustările necesare.' ),
			),
			'wa'       => 'Bună ziua! Am nevoie de proiectare 3D pentru: ',
			'related'  => array( 'printare-3d-personalizata', 'support' ),
		),
	);
}

function p3d_service( $slug ) {
	$all = p3d_services();
	return $all[ $slug ] ?? null;
}

function p3d_service_url( $slug ) {
	return home_url( '/services/' . $slug . '/' );
}

/**
 * Întrebări frecvente generale (prima pagină).
 */
function p3d_home_faq() {
	return array(
		array( 'Reparați imprimante 3D în București?', 'Da. Facem diagnoză, reparații, înlocuire de piese și întreținere pentru cele mai populare imprimante 3D, printre care Bambu Lab, Prusa, Creality, Anycubic, Elegoo și Voron.' ),
		array( 'Ce trebuie să trimit pentru o printare 3D?', 'Modelul 3D al piesei. Dacă nu îl ai, poți aduce piesa existentă sau ne poți descrie ideea, iar noi o proiectăm.' ),
		array( 'Pot alege materialul și culoarea?', 'Da, alegi materialul, culoarea și dimensiunile potrivite nevoilor tale.' ),
		array( 'Cum primesc piesele printate?', 'Poți alege ridicarea personală sau livrarea la domiciliu.' ),
	);
}
