<?php

class GraficoBarras {

    var $num_barras;
    var $max_val;
    var $inc_horiz;
    var $inc_vert;
    var $imag;
    var $arr_data;
    var $cor_do_fundo;
    var $cor_do_titulo;
    var $cor_de_linhas;

    function __construct($num = 3) {
        $this->num_barras = $num;
        $this->arr_data = array();

        $this->imag = imagecreate(400, 300);
        $this->cor_do_fundo = imagecolorallocate($this->imag, 255, 255, 255);
        imagefill($this->imag, 0, 0, $this->cor_do_fundo);
    }

    function adicionarTituloGrafico($titulo) {
        $this->cor_do_titulo = imagecolorallocate($this->imag, 0, 0, 0);
        imagestring($this->imag, 5, (400 / 2) - ((8 * strlen($titulo)) / 2), 0, $titulo, $this->cor_do_titulo);
    }

    function desenhaExterior() {
        $this->cor_de_linhas = imagecolorallocate($this->imag, 0, 0, 0);
        imageline($this->imag, 20, 20, 20, 280, $this->cor_de_linhas);
        imageline($this->imag, 20, 280, 380, 280, $this->cor_de_linhas);
    }

    function desenhaEscVertical() {
        $this->inc_vert = (280 - 20) / 10;
        $k = 0;
        for ($n = 280; $n >= 20; $n = $n - $this->inc_vert) {
            imageline($this->imag, 17, $n, 20, $n, $this->cor_de_linhas);
            imagestring($this->imag, 2, 1, $n - 8, $k, $this->cor_de_linhas);
            $k = $k + 10;
        }
    }

    function desenhaEscHorizontal() {
        $this->inc_horiz = (380 - 20) / $this->num_barras;
        for ($n = 20; $n <= 380; $n = $n + $this->inc_horiz) {
            imageline($this->imag, $n, 280, $n, 283, $this->cor_de_linhas);
        }
    }

    function addDados($dado) {
        array_push($this->arr_data, $dado);
    }

    function setMaxValor($valor) {
        $this->max_val = $valor;
    }

    function criaGrafico() {
        $inc = $this->inc_horiz - 4;
        srand((double) microtime() * 1000000);
        for ($n = 0; $n < count($this->arr_data); $n++) {
            $y_value = ($this->arr_data[$n] * 260) / 300;
            $r = rand(0, 255);
            $g = rand(0, 255);
            $b = rand(0, 255);
            $cor_barras = imagecolorallocate($this->imag, $r, $g, $b);
            imagefilledrectangle($this->imag, (20 + 2) + ($inc) * $n + 4 * $n, 280 - $y_value, (20 + 2) + ($inc * ($n + 1)) + 4 * $n, 280 - 1, $cor_barras);
            imagerectangle($this->imag, (20 + 2) + ($inc) * $n + 4 * $n, 280, (20 + 2) + ($inc * ($n + 1)) + 4 * $n, 280 - $y_value, $this->cor_de_linhas);
        }
    }

    function desenhaGrafico() {
        imagepng($this->imag);
        imagedestroy($this->imag);
    }

}

?>
