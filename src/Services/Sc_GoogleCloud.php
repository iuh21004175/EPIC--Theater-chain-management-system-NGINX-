<?php
    namespace App\Services;

    use Google_Client;
    use Google\Service\Calendar;

    class Sc_GoogleCloud {
        private $service;
        private $calendarId;

        public function __construct() {
            // Constructor code here
            $client = new Google_Client();
            $client->setDeveloperKey($_ENV['CALENDAR_API_KEY']);

            $service = new Calendar($client);
            $this->service = $service;

            // Calendar ID cho lịch Ngày lễ Việt Nam
            $this->calendarId = 'vi.vietnamese#holiday@group.v.calendar.google.com';
        }
        public function docNgayLe($batDau, $ketThuc){
             // Thiết lập thời gian bắt đầu và kết thúc của khoảng thời gian
            $listNgayLe = [];
            $timeMin = (new \DateTime($batDau))->setTime(0, 0, 0)->format(\DateTime::RFC3339);
            $timeMax = (new \DateTime($ketThuc))->setTime(23, 59, 59)->format(\DateTime::RFC3339);
            $optParams = array(
                'timeMin' => $timeMin,
                'timeMax' => $timeMax,
                'singleEvents' => true,
                'orderBy' => 'startTime'
            );
            $results = $this->service->events->listEvents($this->calendarId, $optParams);
            foreach ($results->getItems() as $event) {
                $listNgayLe[] = [
                    'ten' => $event->getSummary(),
                    'ngay' => $event->getStart()->getDate()
                ];
            }
            return $listNgayLe;
        }
    }
?>