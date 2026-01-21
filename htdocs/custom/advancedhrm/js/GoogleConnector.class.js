class GoogleConnector{

	startTown='';
	endTown='';
	msg='';
	distanceFinale_km = 0;

	directionsService;
	directionsRenderer;
	fk_user;
	map;

	constructor(id_ex_kme,fk_user){
		this.directionsService = new google.maps.DirectionsService();
		this.directionsRenderer = new google.maps.DirectionsRenderer();
		this.fk_user = fk_user;

		this.ID_EX_KME = id_ex_kme // id de frais kilometrique dans la table llx_c_type_fees
		const StartingPoint = { lat: 48.52, lng: 2.19 };
		// The map, centered at paris
		this.map = new google.maps.Map(document.getElementById("map"), {
			zoom: 4,
			center: StartingPoint,
		});
		this.directionsRenderer.setMap(this.map);
	}
}
