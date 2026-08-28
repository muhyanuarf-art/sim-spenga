<?php

/**
 * Penulis berkas .docx sederhana tanpa pustaka luar.
 *
 * .docx sebenarnya arsip ZIP berisi XML. Kelas ini menyusun bagian yang
 * benar-benar diperlukan Word: tipe konten, relasi, gaya, penomoran, dan
 * isi dokumennya sendiri.
 */
class Docx
{
    private array $isi = [];

    /** Tandai **tebal** dan `kode` di dalam teks biasa. */
    private function larik(string $teks, array $opsi = []): string
    {
        $bagian = preg_split('/(\*\*.+?\*\*|`.+?`)/u', $teks, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $xml = '';

        foreach ($bagian as $b) {
            $tebal = false;
            $kode = false;

            if (str_starts_with($b, '**') && str_ends_with($b, '**')) {
                $b = substr($b, 2, -2);
                $tebal = true;
            } elseif (str_starts_with($b, '`') && str_ends_with($b, '`')) {
                $b = substr($b, 1, -1);
                $kode = true;
            }

            $rpr = '';
            if ($tebal || ($opsi['tebal'] ?? false)) {
                $rpr .= '<w:b/>';
            }
            if ($kode) {
                $rpr .= '<w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:color w:val="B03060"/>';
            }
            if ($opsi['warna'] ?? false) {
                $rpr .= '<w:color w:val="'.$opsi['warna'].'"/>';
            }
            if ($opsi['ukuran'] ?? false) {
                $rpr .= '<w:sz w:val="'.$opsi['ukuran'].'"/>';
            }
            if ($opsi['italic'] ?? false) {
                $rpr .= '<w:i/>';
            }

            $xml .= '<w:r>'.($rpr ? '<w:rPr>'.$rpr.'</w:rPr>' : '')
                .'<w:t xml:space="preserve">'.htmlspecialchars($b, ENT_XML1).'</w:t></w:r>';
        }

        return $xml;
    }

    public function sampulJudul(string $judul, string $subjudul, array $baris = []): self
    {
        $this->isi[] = '<w:p><w:pPr><w:spacing w:before="3000" w:after="0"/><w:jc w:val="center"/></w:pPr>'
            .'<w:r><w:rPr><w:b/><w:sz w:val="72"/><w:color w:val="1F4E79"/></w:rPr>'
            .'<w:t xml:space="preserve">'.htmlspecialchars($judul, ENT_XML1).'</w:t></w:r></w:p>';

        $this->isi[] = '<w:p><w:pPr><w:spacing w:after="600"/><w:jc w:val="center"/></w:pPr>'
            .'<w:r><w:rPr><w:sz w:val="32"/><w:color w:val="404040"/></w:rPr>'
            .'<w:t xml:space="preserve">'.htmlspecialchars($subjudul, ENT_XML1).'</w:t></w:r></w:p>';

        foreach ($baris as $b) {
            $this->isi[] = '<w:p><w:pPr><w:spacing w:after="80"/><w:jc w:val="center"/></w:pPr>'
                .$this->larik($b, ['ukuran' => 22, 'warna' => '595959']).'</w:p>';
        }

        return $this;
    }

    public function h1(string $t): self
    {
        $this->isi[] = '<w:p><w:pPr><w:pStyle w:val="Heading1"/><w:pageBreakBefore/></w:pPr>'.$this->larik($t).'</w:p>';

        return $this;
    }

    public function h2(string $t): self
    {
        $this->isi[] = '<w:p><w:pPr><w:pStyle w:val="Heading2"/></w:pPr>'.$this->larik($t).'</w:p>';

        return $this;
    }

    public function h3(string $t): self
    {
        $this->isi[] = '<w:p><w:pPr><w:pStyle w:val="Heading3"/></w:pPr>'.$this->larik($t).'</w:p>';

        return $this;
    }

    public function p(string $t): self
    {
        $this->isi[] = '<w:p><w:pPr><w:spacing w:after="140" w:line="276" w:lineRule="auto"/><w:jc w:val="both"/></w:pPr>'
            .$this->larik($t).'</w:p>';

        return $this;
    }

    /** @param list<string> $poin */
    public function poin(array $poin): self
    {
        foreach ($poin as $b) {
            $this->isi[] = '<w:p><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr>'
                .'<w:spacing w:after="60" w:line="276" w:lineRule="auto"/></w:pPr>'.$this->larik($b).'</w:p>';
        }

        return $this;
    }

    /** @param list<string> $langkah */
    public function langkah(array $langkah): self
    {
        foreach ($langkah as $b) {
            $this->isi[] = '<w:p><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="2"/></w:numPr>'
                .'<w:spacing w:after="80" w:line="276" w:lineRule="auto"/></w:pPr>'.$this->larik($b).'</w:p>';
        }

        return $this;
    }

    public function kode(string $teks): self
    {
        $sel = '';
        foreach (explode("\n", $teks) as $baris) {
            $sel .= '<w:p><w:pPr><w:spacing w:after="0" w:line="240" w:lineRule="auto"/></w:pPr>'
                .'<w:r><w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:sz w:val="18"/></w:rPr>'
                .'<w:t xml:space="preserve">'.htmlspecialchars($baris, ENT_XML1).'</w:t></w:r></w:p>';
        }

        $this->isi[] = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/>'
            .'<w:tblBorders><w:top w:val="single" w:sz="4" w:color="D9D9D9"/><w:left w:val="single" w:sz="4" w:color="D9D9D9"/>'
            .'<w:bottom w:val="single" w:sz="4" w:color="D9D9D9"/><w:right w:val="single" w:sz="4" w:color="D9D9D9"/></w:tblBorders>'
            .'<w:tblCellMar><w:top w:w="120" w:type="dxa"/><w:left w:w="160" w:type="dxa"/>'
            .'<w:bottom w:w="120" w:type="dxa"/><w:right w:w="160" w:type="dxa"/></w:tblCellMar></w:tblPr>'
            .'<w:tr><w:tc><w:tcPr><w:shd w:val="clear" w:fill="F5F5F5"/></w:tcPr>'.$sel.'</w:tc></w:tr></w:tbl>'
            .'<w:p><w:pPr><w:spacing w:after="140"/></w:pPr></w:p>';

        return $this;
    }

    public function catatan(string $judul, string $teks, string $warna = 'FFF4CE'): self
    {
        $this->isi[] = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/>'
            .'<w:tblBorders><w:top w:val="single" w:sz="4" w:color="E0C878"/><w:left w:val="single" w:sz="18" w:color="E0A800"/>'
            .'<w:bottom w:val="single" w:sz="4" w:color="E0C878"/><w:right w:val="single" w:sz="4" w:color="E0C878"/></w:tblBorders>'
            .'<w:tblCellMar><w:top w:w="140" w:type="dxa"/><w:left w:w="200" w:type="dxa"/>'
            .'<w:bottom w:w="140" w:type="dxa"/><w:right w:w="200" w:type="dxa"/></w:tblCellMar></w:tblPr>'
            .'<w:tr><w:tc><w:tcPr><w:shd w:val="clear" w:fill="'.$warna.'"/></w:tcPr>'
            .'<w:p><w:pPr><w:spacing w:after="60"/></w:pPr>'.$this->larik($judul, ['tebal' => true]).'</w:p>'
            .'<w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="both"/></w:pPr>'.$this->larik($teks).'</w:p>'
            .'</w:tc></w:tr></w:tbl><w:p><w:pPr><w:spacing w:after="140"/></w:pPr></w:p>';

        return $this;
    }

    /**
     * @param  list<string>  $kepala
     * @param  list<list<string>>  $baris
     * @param  list<int>|null  $lebar  persentase per kolom
     */
    public function tabel(array $kepala, array $baris, ?array $lebar = null): self
    {
        $n = count($kepala);
        $lebar ??= array_fill(0, $n, (int) floor(100 / $n));

        $xml = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/>'
            .'<w:tblBorders><w:top w:val="single" w:sz="4" w:color="BFBFBF"/><w:left w:val="single" w:sz="4" w:color="BFBFBF"/>'
            .'<w:bottom w:val="single" w:sz="4" w:color="BFBFBF"/><w:right w:val="single" w:sz="4" w:color="BFBFBF"/>'
            .'<w:insideH w:val="single" w:sz="4" w:color="BFBFBF"/><w:insideV w:val="single" w:sz="4" w:color="BFBFBF"/></w:tblBorders>'
            .'<w:tblCellMar><w:top w:w="90" w:type="dxa"/><w:left w:w="130" w:type="dxa"/>'
            .'<w:bottom w:w="90" w:type="dxa"/><w:right w:w="130" w:type="dxa"/></w:tblCellMar></w:tblPr>';

        $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
        foreach ($kepala as $i => $k) {
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="'.($lebar[$i] * 50).'" w:type="pct"/>'
                .'<w:shd w:val="clear" w:fill="1F4E79"/></w:tcPr>'
                .'<w:p><w:pPr><w:spacing w:after="0"/></w:pPr>'
                .$this->larik($k, ['tebal' => true, 'warna' => 'FFFFFF', 'ukuran' => 19]).'</w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        foreach ($baris as $r => $b) {
            $shd = $r % 2 ? '<w:shd w:val="clear" w:fill="F7F9FC"/>' : '';
            $xml .= '<w:tr>';
            foreach ($b as $i => $sel) {
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="'.(($lebar[$i] ?? 20) * 50).'" w:type="pct"/>'.$shd.'</w:tcPr>'
                    .'<w:p><w:pPr><w:spacing w:after="0"/></w:pPr>'.$this->larik((string) $sel, ['ukuran' => 19]).'</w:p></w:tc>';
            }
            $xml .= '</w:tr>';
        }

        $this->isi[] = $xml.'</w:tbl><w:p><w:pPr><w:spacing w:after="140"/></w:pPr></w:p>';

        return $this;
    }

    public function simpan(string $path): void
    {
        @unlink($path);
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>'
            .'</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>');

        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>'
            .'</Relationships>');

        $zip->addFromString('word/styles.xml', $this->gayaXml());
        $zip->addFromString('word/numbering.xml', $this->penomoranXml());

        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .implode('', $this->isi)
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
            .'<w:pgMar w:top="1418" w:right="1276" w:bottom="1418" w:left="1418" w:header="709" w:footer="709"/>'
            .'</w:sectPr></w:body></w:document>');

        $zip->close();
    }

    private function gayaXml(): string
    {
        $h = fn (string $id, string $nama, int $sz, string $warna, int $before) =>
            '<w:style w:type="paragraph" w:styleId="'.$id.'"><w:name w:val="'.$nama.'"/>'
            .'<w:basedOn w:val="Normal"/><w:qFormat/>'
            .'<w:pPr><w:keepNext/><w:spacing w:before="'.$before.'" w:after="140"/><w:outlineLvl w:val="'.(substr($id, -1) - 1).'"/></w:pPr>'
            .'<w:rPr><w:b/><w:sz w:val="'.$sz.'"/><w:color w:val="'.$warna.'"/></w:rPr></w:style>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr>'
            .'<w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/><w:lang w:val="id-ID"/>'
            .'</w:rPr></w:rPrDefault></w:docDefaults>'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>'
            .$h('Heading1', 'heading 1', 34, '1F4E79', 400)
            .$h('Heading2', 'heading 2', 27, '2E74B5', 320)
            .$h('Heading3', 'heading 3', 23, '404040', 240)
            .'</w:styles>';
    }

    private function penomoranXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0"><w:start w:val="1"/>'
            .'<w:numFmt w:val="bullet"/><w:lvlText w:val="•"/><w:lvlJc w:val="left"/>'
            .'<w:pPr><w:ind w:left="454" w:hanging="284"/></w:pPr>'
            .'<w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:hint="default"/></w:rPr></w:lvl></w:abstractNum>'
            .'<w:abstractNum w:abstractNumId="1"><w:lvl w:ilvl="0"><w:start w:val="1"/>'
            .'<w:numFmt w:val="decimal"/><w:lvlText w:val="%1."/><w:lvlJc w:val="left"/>'
            .'<w:pPr><w:ind w:left="454" w:hanging="340"/></w:pPr></w:lvl></w:abstractNum>'
            .'<w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num>'
            .'<w:num w:numId="2"><w:abstractNumId w:val="1"/></w:num>'
            .'</w:numbering>';
    }
}
