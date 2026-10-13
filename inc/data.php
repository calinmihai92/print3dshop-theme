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
				array( 'Nu mai extrudează', 'Duză înfundată, extruder care sare sau filament care nu mai iese.' ),
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
			'related'  => array( 'printare-3d-personalizata', 'prototyping', 'prelucrare-cnc-matrite' ),
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
			'related'  => array( 'prototyping', 'scanare-3d', 'support' ),
		),

		'prototyping' => array(
			'label'    => 'Proiectare 3D',
			'icon'     => 'pen',
			'eyebrow'  => 'Proiectare și modelare 3D',
			'h1a'      => 'Proiectare 3D:',
			'h1b'      => 'dă formă ideilor tale',
			'lead'     => 'Pornim de la ideea sau schița ta și creăm modelul 3D, gata de printat. Facem și desene tehnice 2D (CAD) cu cote, pentru producție, CNC sau documentație.',
			'card'     => 'De la schiță la model 3D gata de printat. Ideal când nu ai încă fișierul piesei.',
			'points'   => array( 'De la schiță la model', 'Desen tehnic 2D (CAD)', 'Printare opțională' ),
			'also'     => array(
				'title' => array( 'Și ', 'desen tehnic 2D' ),
				'lead'  => 'Când piesa trebuie executată în alt atelier (CNC, strunjire, tăiere) sau ai nevoie de documentație, îți facem desenul tehnic.',
				'items' => array(
					array( 'doc', 'Desen tehnic cu cote', 'Vederi, secțiuni, cote și toleranțe, după piesa ta, după o schiță sau după modelul 3D.' ),
					array( 'gear', 'Pentru producție și CNC', 'Desene pregătite pentru atelierele de prelucrare, inclusiv pentru serviciul nostru de CNC și matrițe.' ),
					array( 'layers', 'Fișier CAD și PDF', 'Primești desenul în format CAD, editabil, și în PDF, gata de printat sau de trimis.' ),
				),
			),
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
				array( 'Faceți și desene tehnice 2D?', 'Da. Facem desene tehnice 2D (CAD) cu cote, vederi și secțiuni, după piesa ta, după o schiță sau după modelul 3D. Primești fișierul CAD și PDF.' ),
			),
			'wa'       => 'Bună ziua! Am nevoie de proiectare 3D / desen tehnic pentru: ',
			'related'  => array( 'printare-3d-personalizata', 'prelucrare-cnc-matrite', 'design-grafic-tipar' ),
		),

		'prelucrare-cnc-matrite' => array(
			'label'    => 'Prelucrare CNC și matrițe',
			'short'    => 'CNC și matrițe',
			'icon'     => 'gear',
			'eyebrow'  => 'Prelucrare CNC și matrițe · la comandă',
			'h1a'      => 'Prelucrare CNC',
			'h1b'      => 'și matrițe la comandă',
			'lead'     => 'Ai nevoie de piese din metal sau plastic tehnic, de o serie mai mare sau de o matriță? Te ajutăm de la idee până la piesa finită: analizăm proiectul împreună, alegem soluția potrivită și coordonăm execuția în ateliere CNC partenere, specializate.',
			'card'     => 'Piese prelucrate CNC și matrițe, prin consultanță și la comandă. Execuția se face în ateliere partenere.',
			'points'   => array( 'Consultanță tehnică', 'Execuție în ateliere partenere', 'Un singur interlocutor' ),
			'quote'    => true,
			'whatTitle' => array( 'Ce ', 'putem realiza' ),
			'what'     => array(
				array( 'gear', 'Piese prelucrate CNC', 'Frezare și strunjire CNC pentru piese din aluminiu, oțel, alamă sau plastice tehnice.' ),
				array( 'cube', 'Matrițe și serii de producție', 'Matrițe pentru turnare sau injecție, când piesa trebuie produsă în serie.' ),
				array( 'layers', 'De la prototip la serie', 'Testezi forma prin printare 3D, apoi trecem la CNC sau matriță pentru producție.' ),
				array( 'pen', 'Desen tehnic și model 3D', 'Dacă nu ai desen sau model, îl realizăm noi, pregătit pentru execuție.' ),
				array( 'chat', 'Consultanță', 'Te ajutăm să alegi materialul, toleranțele și procedeul potrivit, ca să nu plătești mai mult decât e nevoie.' ),
			),
			'stepsTitle' => array( 'Cum ', 'lucrăm' ),
			'steps'    => array(
				array( 'Ne trimiți proiectul', 'Desen, model 3D, o piesă existentă sau doar ideea, plus cantitatea de care ai nevoie.' ),
				array( 'Consultanță', 'Analizăm piesa împreună și stabilim materialul, procedeul și toleranțele.' ),
				array( 'Ofertă', 'Cerem oferte de la atelierele partenere și îți trimitem prețul și termenul.' ),
				array( 'Execuție', 'După confirmare, piesele sau matrița se execută la partenerul potrivit.' ),
				array( 'Verificare și predare', 'Verificăm piesele și ți le predăm la atelier sau prin curier.' ),
			),
			'faq'      => array(
				array( 'Executați voi piesele CNC?', 'Prelucrarea CNC și matrițele se execută în ateliere partenere, specializate. Noi ne ocupăm de consultanță, de proiect și de coordonare, iar tu ai un singur interlocutor de la cerere până la predare.' ),
				array( 'Cât costă o piesă CNC sau o matriță?', 'Prețul depinde de material, dimensiuni, complexitate și cantitate, de aceea facem ofertă pentru fiecare proiect. Trimite-ne desenul sau modelul și revenim cu prețul și termenul.' ),
				array( 'Ce trebuie să trimit pentru ofertă?', 'Ideal, un desen tehnic sau un model 3D (STEP), materialul dorit și cantitatea. Dacă nu ai desen, pornim de la o piesă existentă sau de la o schiță.' ),
				array( 'Faceți și bucăți unicat?', 'Da, de la o singură piesă până la serii de producție. Pentru o singură piesă îți spunem sincer dacă printarea 3D e o variantă mai avantajoasă.' ),
			),
			'wa'       => 'Bună ziua! Aș vrea o ofertă pentru prelucrare CNC / matrițe: ',
			'preset'   => 'Prelucrare CNC / matrițe',
			'waBtn'    => 'Trimite desenul pe WhatsApp',
			'ctaTitle' => 'Ai o piesă de executat?',
			'ctaText'  => 'Trimite-ne desenul sau modelul și cantitatea, iar noi revenim cu oferta.',
			'related'  => array( 'prototyping', 'scanare-3d', 'printare-3d-personalizata' ),
		),

		'design-grafic-tipar' => array(
			'label'    => 'Design grafic și tipar',
			'icon'     => 'palette',
			'eyebrow'  => 'Design grafic și tipar · la comandă',
			'h1a'      => 'Design grafic',
			'h1b'      => 'și tipar pentru firma ta',
			'lead'     => 'Logo, flyere, broșuri, cataloage și prezentări de firmă: le gândim, le desenăm și, dacă vrei, le tipărim prin tipografia noastră parteneră. Ai un singur interlocutor, de la idee până la materialele tipărite.',
			'card'     => 'Logo, flyere, broșuri, cataloage și prezentări de firmă. Design la comandă și tipar prin tipografie parteneră.',
			'points'   => array( 'Design la comandă', 'Tipar prin tipografie parteneră', 'Fișiere gata de tipar' ),
			'quote'    => true,
			'whatTitle' => array( 'Ce ', 'realizăm' ),
			'what'     => array(
				array( 'pen', 'Logo și identitate', 'Logo nou sau vectorizarea logo-ului existent, în fișiere pentru print, web și piese 3D.' ),
				array( 'doc', 'Flyere și broșuri', 'Materiale de prezentare clare, pe formatul și hârtia potrivite, gata de tipar.' ),
				array( 'layers', 'Cataloage', 'Cataloage de produse și liste de prețuri, cu paginare și fotografii.' ),
				array( 'chart', 'Prezentări de firmă', 'Prezentări pentru clienți, parteneri sau finanțări, pe ecran sau tipărite.' ),
				array( 'cube', 'Logo 3D și semnalistică', 'Logo-ul tău transformat în litere 3D, plăcuțe sau obiecte printate 3D.' ),
				array( 'box', 'Tipar', 'Tipărim prin tipografia noastră parteneră: flyere, broșuri, cataloage, cărți de vizită.' ),
			),
			'stepsTitle' => array( 'Cum ', 'lucrăm' ),
			'steps'    => array(
				array( 'Ne spui ce ai nevoie', 'Ce material, pentru ce, în ce cantitate. Ne trimiți logo-ul, textele și pozele pe care le ai.' ),
				array( 'Ofertă', 'Îți trimitem prețul pentru design și, dacă vrei, pentru tipar.' ),
				array( 'Propunere de design', 'Vezi prima variantă și cerem ajustările până e cum o vrei.' ),
				array( 'Fișiere finale', 'Primești fișierele gata de tipar și pentru online (PDF, PNG, vectorial).' ),
				array( 'Tipar și livrare', 'Opțional: tipărim prin tipografia parteneră și îți livrăm materialele.' ),
			),
			'faq'      => array(
				array( 'Tipăriți voi materialele?', 'Tiparul se face prin tipografia noastră parteneră. Noi ne ocupăm de design, de pregătirea fișierelor și de comandă, iar tu ai un singur interlocutor.' ),
				array( 'Cât costă un logo sau un flyer?', 'Depinde de complexitate, de numărul de variante și de cantitatea tipărită, de aceea facem ofertă pentru fiecare proiect. Spune-ne ce ai nevoie și revenim cu prețul.' ),
				array( 'Pot comanda doar designul, fără tipar?', 'Da. Primești fișierele gata de tipar și le poți tipări unde vrei.' ),
				array( 'Puteți vectoriza logo-ul meu existent?', 'Da. Refacem logo-ul în format vectorial, ca să poată fi folosit la orice dimensiune, la tipar, pe web sau la piese printate 3D.' ),
			),
			'wa'       => 'Bună ziua! Aș vrea o ofertă pentru design grafic / tipar: ',
			'preset'   => 'Design grafic și tipar',
			'waBtn'    => 'Scrie-ne pe WhatsApp',
			'ctaTitle' => 'Ai nevoie de materiale pentru firmă?',
			'ctaText'  => 'Spune-ne ce materiale vrei și în ce cantitate, iar noi revenim cu oferta pentru design și tipar.',
			'related'  => array( 'prototyping', 'printare-3d-personalizata', 'scanare-3d' ),
		),

		'scanare-3d' => array(
			'label'    => 'Scanare 3D',
			'icon'     => 'scan',
			'eyebrow'  => 'Scanare 3D · la comandă',
			'h1a'      => 'Scanare 3D',
			'h1b'      => 'din obiect real în model 3D',
			'lead'     => 'Ai o piesă pe care vrei să o copiezi, să o modifici sau să o reproduci? O transformăm în model 3D digital. Lucrăm prin consultanță și la comandă: stabilim împreună ce ai nevoie, iar scanarea se face cu parteneri specializați, cu echipamente profesionale.',
			'card'     => 'Obiectul tău devine model 3D, pentru copii, piese de schimb sau modificări. Prin consultanță și la comandă.',
			'points'   => array( 'Consultanță tehnică', 'Scanare cu parteneri specializați', 'Model gata de printat sau de editat' ),
			'quote'    => true,
			'whatTitle' => array( 'La ce ', 'folosește' ),
			'what'     => array(
				array( 'gear', 'Piese de schimb', 'Copiem piese rupte sau care nu se mai găsesc, ca să le putem reproduce.' ),
				array( 'pen', 'Reverse engineering', 'Din scanare facem un model CAD editabil (STEP), pe care îl poți modifica.' ),
				array( 'cube', 'Copii și reproduceri', 'Obiecte, figurine sau componente reproduse prin printare 3D.' ),
				array( 'search', 'Control dimensional', 'Compari piesa reală cu modelul sau verifici forma și dimensiunile.' ),
			),
			'stepsTitle' => array( 'Cum ', 'lucrăm' ),
			'steps'    => array(
				array( 'Ne spui ce ai', 'Ne trimiți poze cu obiectul, dimensiunile aproximative și ce vrei să faci cu modelul.' ),
				array( 'Consultanță', 'Stabilim tipul de scanare și formatul de care ai nevoie: STL pentru printare sau STEP pentru modificări.' ),
				array( 'Ofertă', 'Îți trimitem prețul și termenul, după ce verificăm cu partenerul de scanare.' ),
				array( 'Scanare și procesare', 'Obiectul se scanează, iar modelul se curăță și se pregătește.' ),
				array( 'Predare', 'Primești modelul 3D, iar dacă vrei, îți printăm și piesa.' ),
			),
			'faq'      => array(
				array( 'Faceți voi scanarea?', 'Scanarea se face cu parteneri specializați, cu echipamente profesionale. Noi ne ocupăm de consultanță, de pregătirea modelului și, dacă vrei, de printare, iar tu ai un singur interlocutor.' ),
				array( 'Cât costă o scanare 3D?', 'Depinde de mărimea obiectului, de detalii și de formatul de care ai nevoie, de aceea facem ofertă pentru fiecare proiect. Trimite-ne câteva poze și revenim cu prețul.' ),
				array( 'Ce primesc după scanare?', 'Un model 3D (STL), gata de printat. Dacă vrei să modifici piesa, putem face și un model CAD editabil (STEP).' ),
				array( 'Puteți și printa piesa după scanare?', 'Da. După scanare putem printa piesa sau o putem modifica înainte de printare.' ),
			),
			'wa'       => 'Bună ziua! Aș vrea o ofertă pentru scanare 3D: ',
			'preset'   => 'Scanare 3D',
			'waBtn'    => 'Trimite poze pe WhatsApp',
			'ctaTitle' => 'Ai un obiect de scanat?',
			'ctaText'  => 'Trimite-ne câteva poze și dimensiunile aproximative, iar noi revenim cu oferta.',
			'related'  => array( 'prototyping', 'printare-3d-personalizata', 'prelucrare-cnc-matrite' ),
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

/**
 * Prețuri orientative (cu TVA) — pagina /preturi/ și blocul „Prețuri” de pe paginile de servicii.
 * Fiecare rând: array( denumire, detaliu (opțional), preț ).
 */
function p3d_prices() {
	return array(
		'service' => array(
			'icon'    => 'wrench',
			'title'   => array( 'Service ', 'imprimante 3D' ),
			'intro'   => 'Pentru Bambu Lab, Prusa, Creality, Anycubic, Elegoo, Voron și alte mărci. Îți spunem costul înainte să începem lucrul.',
			'service' => 'support',
			'rows'    => array(
				array( 'Diagnostic', 'Verificăm imprimanta și îți spunem ce are', 'Gratuit' ),
				array( 'Curățare sau schimbare duză / hotend desfundat', '', 'de la 100 lei + prețul piesei' ),
				array( 'Calibrare completă', 'Nivelare, flow, retracție, profil slicer', 'de la 150 lei' ),
				array( 'Revizie generală', 'Curățare, ungere, curele, calibrare', 'de la 200 lei + prețul pieselor, dacă e cazul' ),
				array( 'Asamblare și calibrare imprimantă nouă', '', 'de la 150 lei' ),
				array( 'Manoperă reparații', 'Pentru defecte care nu intră în operațiile de mai sus', '125 lei/oră + prețul piesei' ),
			),
		),
		'printare' => array(
			'icon'    => 'layers',
			'title'   => array( 'Printare ', '3D' ),
			'intro'   => 'Prețul = materialul folosit + timpul de printare. Fără comandă minimă.',
			'service' => 'printare-3d-personalizata',
			'rows'    => array(
				array( 'PLA', 'Material', '1 leu/g' ),
				array( 'ABS / ASA / TPU', 'Material', '1,5 lei/g' ),
				array( 'Timp de printare', 'Se adaugă la material', '15 lei/oră' ),
			),
			'note'    => 'Exemplu: o piesă din PLA de 50 g, printată în 3 ore, costă 50 lei + 45 lei = 95 lei.',
		),
		'proiectare' => array(
			'icon'    => 'pen',
			'title'   => array( 'Proiectare ', '3D' ),
			'intro'   => 'Pornim de la o idee, o schiță sau o piesă ruptă și facem modelul 3D, gata de printat.',
			'service' => 'prototyping',
			'rows'    => array(
				array( 'Proiectare și modelare 3D', 'Primești fișierul; printarea e opțională', 'de la 150 lei/oră' ),
				array( 'Desen tehnic 2D (CAD)', 'Cote, vederi, secțiuni; fișier CAD și PDF', 'Cere ofertă' ),
			),
		),
		'scanare' => array(
			'icon'    => 'scan',
			'title'   => array( 'Scanare ', '3D' ),
			'intro'   => 'Prin consultanță și la comandă. Scanarea se face cu parteneri specializați, iar prețul depinde de mărimea obiectului și de formatul dorit.',
			'service' => 'scanare-3d',
			'rows'    => array(
				array( 'Scanare 3D', 'Primești modelul 3D (STL), gata de printat', 'Cere ofertă' ),
				array( 'Model CAD editabil (STEP), după scanare', 'Pentru reverse engineering și modificări', 'Cere ofertă' ),
			),
		),
		'cnc' => array(
			'icon'    => 'gear',
			'title'   => array( 'Prelucrare CNC ', 'și matrițe' ),
			'intro'   => 'Prin consultanță și la comandă. Execuția se face în ateliere CNC partenere, iar prețul depinde de material, complexitate și cantitate.',
			'service' => 'prelucrare-cnc-matrite',
			'rows'    => array(
				array( 'Prelucrare CNC', 'Frezare, strunjire; metal sau plastice tehnice', 'Cere ofertă' ),
				array( 'Matrițe', 'Pentru turnare sau injecție, serii de producție', 'Cere ofertă' ),
			),
		),
		'grafica' => array(
			'icon'    => 'palette',
			'title'   => array( 'Design grafic ', 'și tipar' ),
			'intro'   => 'Design la comandă și tipar prin tipografia noastră parteneră. Prețul depinde de complexitate și de cantitate.',
			'service' => 'design-grafic-tipar',
			'rows'    => array(
				array( 'Logo / vectorizare logo', 'Fișiere pentru print, web și piese 3D', 'Cere ofertă' ),
				array( 'Flyere și broșuri', 'Design gata de tipar', 'Cere ofertă' ),
				array( 'Cataloage', 'Cataloage de produse, liste de prețuri', 'Cere ofertă' ),
				array( 'Prezentări de firmă', 'Pe ecran sau tipărite', 'Cere ofertă' ),
				array( 'Tipar', 'Prin tipografia parteneră', 'Cere ofertă' ),
			),
		),
	);
}

function p3d_prices_faq() {
	return array(
		array( 'Prețurile includ TVA?', 'Da, toate prețurile afișate includ TVA.' ),
		array( 'Prețul final poate fi diferit?', 'Prețurile sunt orientative. Prețul final depinde de piesă, de model și de ce trebuie făcut, iar ți-l confirmăm înainte să începem lucrul.' ),
		array( 'Diagnosticul imprimantei chiar e gratuit?', 'Da. Verificăm imprimanta, îți spunem ce are și cât costă reparația. Decizi apoi dacă mergem mai departe.' ),
		array( 'Există o comandă minimă la printare?', 'Nu. Plătești doar materialul folosit și timpul de printare.' ),
		array( 'Cum aflu prețul exact pentru piesa mea?', 'Trimite-ne pe WhatsApp modelul 3D, o poză sau o descriere și îți spunem prețul.' ),
	);
}
