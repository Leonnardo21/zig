<?php 
namespace App\Services\RelatorioXlsService;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer as Writer;

class GerarRelatorioDeVendasPorPeriodoXlsService
{
	protected $titulo;
	protected $nomeDoArquivo;
	protected $diretorio;
	protected $periodo = [];

	public function setTitulo($titulo)
	{
		$this->titulo = $titulo;
	}

	public function setNomeDoArquivo($nomeDoArquivo)
	{
		$this->nomeDoArquivo = $nomeDoArquivo.'.xlsx';
	}

	public function setPeriodo(array $periodo)
	{
		$this->periodo = $periodo;
	}

	public function setDiretorio($diretorio)
	{
		$this->diretorio = $diretorio;
	}

	public function gerarXsl($vendas)
	{
		$spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', "Relatório de vendas por período.{$this->periodo['de']} à {$this->periodo['ate']}");

        $dados = [];
        $dados[] = ['Usuário', 'Valor', 'Tipo Pagamento', 'Hora', 'Data'];

        foreach($vendas as $venda) {
            $dados[] = [
                $venda->nome,
                number_format($venda->valor, 2,',','.'),
                $venda->legenda,
                $venda->hora,
                $venda->data
            ];
        }
     
        # Celula Titulo do Documento
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setSize(15);

        # Celula Usuários
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(25);

        # Celula Valor
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);

        # Celula Tipo de Pagamento
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);

        # Celula Data
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);

        $spreadsheet->getActiveSheet()->getStyle('A2:E2')->getFont()->setBold(true);
        
        # Define o estilo do Header
        $header = [
            'font' => [
                'color' => [
                    'rgb' => 'ffffff'
                ],
            ],
            'fill' => [
                'fillType' => FILL::FILL_SOLID,
                'startColor' => [
                    'rgb' => '339999'
                ]
            ],

            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ]
            ]
        ];
        
        $spreadsheet->getActiveSheet()->getStyle('A2:E2')->applyFromArray($header);
        $sheet->fromArray($dados, NULL,'A2');

        $streamedResponse = new StreamedResponse();
		$streamedResponse->setCallback(function () use ($spreadsheet) {
			$writer =  new Xlsx($spreadsheet);
		    $writer->save('php://output');
		});
        
		$streamedResponse->setStatusCode(200);
		$streamedResponse->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		$streamedResponse->headers->set('Content-Disposition', 'attachment; filename="'.$this->nomeDoArquivo.'"');
		return $streamedResponse->send();

        //$writer = new Xlsx($spreadsheet);
        // $writer->save('php://output');
        //$writer->save($this->diretorio.'/'.$this->nomeDoArquivo);
	}
}
