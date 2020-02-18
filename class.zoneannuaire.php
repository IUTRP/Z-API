<?php
/**
 * ZoneAnnuaire
 */

class ZoneAnnuaire
{
    private $lang = ['MULTI','FR']; // Priority Array - not done
    private $quality = ['HDLIGHT','FULLHD','BLURAY']; // Priority Array - not done
    
    public function getList($name){
        $list = [];
        $output = [];
        $url = 'https://www.zone-annuaire.com/engine/ajax/controller.php?mod=filter&catid=0&q=' .urlencode($name). '&note=0&art=0&AiffchageMode=0&inputTirePar=0&cstart=0';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-Requested-With' => 'XMLHttpRequest']);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        $re = '/cover_global(?:(?:.|\n)*?)data-newsid="(?:(?:.|\n)*?)cover_infos_title(?:(?:.|\n)*?)<a href="https:\/\/www\.zone-annuaire\.com\/(?:.*?)\/(\d*)-(?:(?:.|\n)*?)> {0,}(.*?)<\/a>(?:(?:.|\n)*?)detail_release(?:(?:.|\n)*?)<b>(.*?)<\/span>(?:.*?)"> {0,}(.*?)<\/span>/';
        $result = curl_exec($curl);
        preg_match_all($re, $result, $matches, 0);
        $size = sizeof($matches[1]);
        for($i = 0; $i < $size; $i++ ){
            $list[$i]['id'] = $matches[1][$i];
            $list[$i]['name'] = $matches[2][$i];
            $list[$i]['quality'] = $matches[3][$i];
            $list[$i]['lang'] = $matches[4][$i];
        }
        /*
         * Now lets re-order them !
         */
        /*
         * Language ( no done yet )
         */

        /*
         * Quality per Language ( not done yet )
         */
        return $list; // Temporary return
    }

    public function bypassURL($url){
        $reg = '/(https:\/\/zt-protect\.com\/(to|link)\/(.*))/';
        preg_match($reg, $url,$result, PREG_OFFSET_CAPTURE, 0);
        if($result){
            if($result[2][0] == 'to'){
               $initialReq = curl_init('https://zt-protect.com/link/' . $result[3][0]);
                curl_setopt($initialReq,CURLOPT_RETURNTRANSFER, true);
            }else{
                $initialReq = curl_init($url);
                curl_setopt($initialReq,CURLOPT_RETURNTRANSFER, true);
            }
            $result = curl_exec($initialReq);
            $rGetURL = '/<p class="showURL">(.*?)</';
            preg_match($rGetURL, $result,$link, PREG_OFFSET_CAPTURE, 0);
            if($link){
                return htmlspecialchars_decode($link[1][0]);
            }
        }
        return $url;
    }
    public function getInformation($id){
        $output = [
            'name' => 'unknown',
            'quality' => '',
            'type' => [
                'name' => 'unknown',
                // length - film only
                // n. episodes - series only
                // season - series
            ],
            'origin' => [],
            'director' => [],
            'actor' => [],
            'genre' => [],
            'synopsis' => 'unknown',
            'download' => [
                'parts' => [],
                'full' => []
            ]
            # date - film only
        ];
        $curl = curl_init('https://www.zone-annuaire.com/index.php?newsid=' . (int)$id);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($curl);
        $title = '/<title>(.*?) &raquo; Zone-Annuaire - Zone Telechargement gratuit \(ZT \+AT \)<\/title>/';
        $rQuality = '/<div class="smallsep">(?:.*?)Qualité (.*?) \| (.*?) {0,1}</i';
        $isFilm = '/<span class="link_cat" style="padding:0 4px 0 4px;font-size:12px;"><a href="(?:.*?)">Films<\/a>/';
        $isSeries = '/<span class="link_cat" style="padding:0 4px 0 4px;font-size:12px;"><a href="(?:.*?)">Séries<\/a>/';
        $getDesc = '/synopsis\.png"(?:.*?)(?:<em>|<i>)([a-zA-Z1-9.?-`|()éà^,çè \'-ê]*?)<\//';
        preg_match($isFilm, $result, $boolFilm, PREG_OFFSET_CAPTURE, 0);
        $SubDesc = '/<u>(.*?)<\/u> :(?:<\/b>|<\/strong>) (.*?) {0,1}</';
        preg_match_all($SubDesc, $result, $globalinfo, PREG_OFFSET_CAPTURE, 0);
        preg_match($title, $result, $titleinfo, PREG_OFFSET_CAPTURE, 0);
        preg_match($getDesc, $result, $description, PREG_OFFSET_CAPTURE, 0);
        preg_match($rQuality, $result, $theQuality, PREG_OFFSET_CAPTURE, 0);
        if($boolFilm){
            $output['type']['name'] = 'film';
            $output['name'] = $titleinfo[1][0];
            $output['quality'] = $theQuality[1][0];
            $output['synopsis'] = $description[1][0];
            $size = sizeof($globalinfo[1]);
            for($i = 0; $i < $size; $i++ ){
                switch($globalinfo[1][$i][0]){
                    case 'Origine':
                        $output['origin'] = explode(',',$globalinfo[2][$i][0]);
                        break;
                    case 'Réalisation':
                        $output['director'] = explode(',',$globalinfo[2][$i][0]);
                        break;
                    case 'Durée':
                        $output['type']['length'] =$globalinfo[2][$i][0];
                        break;
                    case 'Acteur(s)':
                        $output['actor'] = explode(',',$globalinfo[2][$i][0]);
                        break;
                    case 'Genre':
                        $output['genre'] = explode(',',$globalinfo[2][$i][0]);
                        break;
                    case 'Date de sortie':
                        $output['release_date'] =$globalinfo[2][$i][0];
                        break;
                }
            }
                /*
                 * Parts
                 */
                $rGetParts = '/<div style="(?:.*?)>(Uptobox|Uploaded|Turbobit|Nitroflare|1fichier|Rapidgator)<\/div>(?:.*?)(<a class="btnToLink" target="_blank" href="(?:.*?)">(?:Partie|Part|Parts) {0,}\d {0,}<\/a>(?:<br>)*)<br><\/b><br \/><br \/><br \/><b>/';
                preg_match_all($rGetParts, $result, $HostingParts, PREG_SET_ORDER, 0);
                $rGetPartsURL = '/(https:\/\/zt-protect\.com(?:.*?))"/';
                if($HostingParts) {
                    foreach ($HostingParts as $Hosters) {
                        $count = 1;
                        preg_match_all($rGetPartsURL, $Hosters[2], $links, PREG_SET_ORDER, 0);
                        $output['download']['parts'][$Hosters[1]] = [];
                        foreach ($links as $link) {
                            $output['download']['parts'][$Hosters[1]][$count] = $link[1];
                            $count++;
                        }
                    }
                }
                /*
                 * Premium
                 */
                $rGetPremiumParts = '/(<div style="(?:.*?)>(?:Uptobox|Uploaded|Turbobit|Nitroflare|1fichier|Rapidgator)<\/div>(?:.*?)<a class="btnToLink" target="_blank" href="(?:.*?)">Télécharger(?:.*?)<\/b><br \/><br \/><br \/><br \/>)/';
                preg_match($rGetPremiumParts, $result, $PremiumHostingParts, PREG_OFFSET_CAPTURE, 0);
                if($PremiumHostingParts){
                    $PremiumHTML = $PremiumHostingParts[0][0];
                    $rGetPremiumLinks = '/(Uptobox|Uploaded|Turbobit|Nitroflare|1fichier|Rapidgator)(?:.*?)href="(.*?)"/';
                    preg_match_all($rGetPremiumLinks, $PremiumHTML, $premiumLinks, PREG_SET_ORDER, 0);
                    foreach($premiumLinks as $premiumLink){
                        $output['download']['full'][$premiumLink[1]] = $this->bypassURL($premiumLink[2]);
                    }
                }

        }else {
            preg_match($isSeries, $result, $boolSeries, PREG_OFFSET_CAPTURE, 0);
            if ($boolSeries) {
                $output['type']['name'] = 'series';
                $output['name'] = $titleinfo[1][0];
                $output['synopsis'] = $description[1][0];
            }else{
                $output['type']['name'] = 'unsupported';
                return $output;
            }
        }
        return $output;
    }
}
