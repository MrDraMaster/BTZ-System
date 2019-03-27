jQuery(document).ready(function($){
    // alle class="hidden_name" Elemente verstecken
    $(".hidden_name").hide();
    
    // wenn Kategorie sich ändert:
    $('#Kategorie').change( function(){
        let value = this.value;
        
        switch(value)
        {
            case "AEAP": // AEAP aufdecken
                $('#Eingabe_AEAP').show();
                $('#Eingabe_Mitarbeiter').hide();
                $('#Eingabe_Teilnehmer').hide();
                break;
            case "Mitarbeiter": // Mitarbeiter aufdecken
                $('#Eingabe_Mitarbeiter').show();
                $('#Eingabe_AEAP').hide();
                $('#Eingabe_Teilnehmer').hide();                
				break;
            case "Teilnehmer": // Teilnehmer aufdecken
                $('#Eingabe_Teilnehmer').show();
                $('#Eingabe_Mitarbeiter').hide();
                $('#Eingabe_AEAP').hide();
                break;
            default: // wenn nichts ausgewählt, alle verstecken
                $('.hidden_name').hide();
        }
        
    })
});