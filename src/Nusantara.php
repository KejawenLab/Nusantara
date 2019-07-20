<?php

declare(strict_types=1);

namespace KejawenLab\Nusantara;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;

/**
 * @author Muhamad Surya Iksanudin <surya.iksanudin@gmail.com>
 */
class Nusantara
{
    public const SCOPE_PROPINSI = 'prop';
    public const SCOPE_KABUPATEN = 'kab';
    public const SCOPE_KECAMATAN = 'kec';
    public const SCOPE_DESA = 'desa';

    public const NUSANTARA_URL = 'http://mfdonline.bps.go.id/index.php?link=hasil_pencarian';

    public function fetch(string $scope = self::SCOPE_DESA, OutputInterface $output = null): array
    {
        $searchKeys = ['a', 'i', 'u', 'e', 'o'];
        $client = new CurlHttpClient();
        $results = [];

        if ($output) {
            $output->writeln(sprintf('<comment>Batas pencarian adalah sampai level "<info>%s</info>"</comment>', ucfirst($scope)));
        }

        foreach ($searchKeys as $searchKey) {
            try {
                if ($output) {
                    $output->writeln(sprintf('<info>Mengambil data menggunakan "<comment>%s</comment>" sebagai kata kunci pencarian.</info>', $searchKey));
                }

                $response = $client->request('POST', self::NUSANTARA_URL, [
                    'body' => [
                        'pilihcari' => $scope,
                        'kata_kunci' => $searchKey,
                    ]
                ]);

                if ($output) {
                    $output->writeln('<info>Menunggu respon dari server.</info>');
                }

                $crawler = new Crawler($response->getContent());
                $trs = $crawler->filterXPath('//tr[@class="table_content"]');

                $progress = null;
                if ($output) {
                    $output->writeln('<info>Memproses data.</info>');
                    $progress = new ProgressBar($output, $trs->count());
                }

                $trs->each(function (Crawler $tr) use (&$results, $scope, $output, $progress) {
                    if ($output) {
                        $progress->advance();
                    }

                    $tds = $tr->filterXPath('//td');

                    $provinceCode = trim($tds->eq(1)->text());
                    $provinceName = trim($tds->eq(2)->text());
                    if (!array_key_exists($provinceCode, $results)) {
                        $results[$provinceCode] = [
                            'name' => $provinceName,
                            'district' => [],
                        ];
                    }

                    if ($scope !== self::SCOPE_PROPINSI) {
                        $districtCode = sprintf('%s%s', $provinceCode, trim($tds->eq(3)->text()));
                        $districtName = trim($tds->eq(4)->text());
                        if (!array_key_exists($districtCode, $results[$provinceCode]['district'])) {
                            $results[$provinceCode]['district'][$districtCode] = [
                                'name' => $districtName,
                                'sub_district' => [],
                            ];
                        }

                        if ($scope === self::SCOPE_KECAMATAN) {
                            $subDistrictCode = sprintf('%s%s', $districtCode, trim($tds->eq(5)->text()));
                            $subDistrictName = trim($tds->eq(6)->text());
                            if (!array_key_exists($subDistrictCode, $results[$provinceCode]['district'][$districtCode]['sub_district'])) {
                                $results[$provinceCode]['district'][$districtCode]['sub_district'][$subDistrictCode] = [
                                    'name' => $subDistrictName,
                                    'village' => [],
                                ];
                            }
                        } else if ($scope === self::SCOPE_DESA) {
                            $subDistrictCode = sprintf('%s%s', $districtCode, trim($tds->eq(5)->text()));
                            $subDistrictName = trim($tds->eq(6)->text());
                            if (!array_key_exists($subDistrictCode, $results[$provinceCode]['district'][$districtCode]['sub_district'])) {
                                $results[$provinceCode]['district'][$districtCode]['sub_district'][$subDistrictCode] = [
                                    'name' => $subDistrictName,
                                    'village' => [],
                                ];
                            }

                            $villageCode = sprintf('%s%s', $subDistrictCode, trim($tds->eq(7)->text()));
                            $villageName = trim($tds->eq(8)->text());
                            if (!array_key_exists($villageCode, $results[$provinceCode]['district'][$districtCode]['sub_district'][$subDistrictCode]['village'])) {
                                $results[$provinceCode]['district'][$districtCode]['sub_district'][$subDistrictCode]['village'][$villageCode] = $villageName;
                            }
                        }
                    }
                });

                if ($output) {
                    $progress->finish();
                    $output->writeln('');
                }
            } catch (ServerException $e) {
                if ($output) {
                    $output->writeln(sprintf('<error>Server error dengan kode: %s</error>', $e->getResponse()->getStatusCode()));
                    $output->writeln(sprintf('<error>Pesan error: %s</error>', $e->getResponse()->getContent()));
                }
            } catch (TransportException $e) {
            }
        }

        return $results;
    }
}
