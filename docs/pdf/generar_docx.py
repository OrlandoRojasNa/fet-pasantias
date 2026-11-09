"""Genera las versiones en Word (APA 7) del manual de usuario y del manual de despliegue.

Requiere pandoc, python-docx y, para el diagrama, Node.js con Playwright y el paquete mermaid.
Uso: python3 docs/pdf/generar_docx.py
"""
import os, re, subprocess, sys, tempfile
from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor

AUTORES = ['Orlando Rojas Narváez', 'Hernán Felipe Patiño Rico', 'Juan José Sabogal García']
PROGRAMA = 'Programa de Ingeniería de Software, Fundación Escuela Tecnológica de Neiva Jesús Oviedo Pérez'
CURSO = 'Ingeniería de Software II'
DOCENTE = ''
FECHA = '5 de noviembre de 2026'
DOCS = [
    ('docs/manual/MANUAL_USUARIO.md', 'docs/word/Manual_de_Usuario_Pasantias_FET_v2.0.0.docx'),
    ('docs/MANUAL_DESPLIEGUE.md', 'docs/word/Manual_de_Despliegue_Pasantias_FET_v2.0.0.docx'),
]
FUENTE = 'Times New Roman'
AQUI = os.path.dirname(os.path.abspath(__file__))


def preparar_md(md_path, tmp):
    texto = open(md_path, encoding='utf-8').read()
    n = 0

    def diagrama(m):
        nonlocal n
        n += 1
        mmd = os.path.join(tmp, f'diagrama{n}.mmd')
        png = os.path.join(tmp, f'diagrama{n}.png')
        open(mmd, 'w', encoding='utf-8').write(m.group(1))
        subprocess.run(['node', os.path.join(AQUI, 'mermaid_png.js'), mmd, png], check=True)
        return f'![Diagrama]({png})'

    texto = re.sub(r'```mermaid\n(.*?)```', diagrama, texto, flags=re.S)
    salida = os.path.join(tmp, 'entrada.md')
    open(salida, 'w', encoding='utf-8').write(texto)
    titulo = re.search(r'^# (.+)$', texto, flags=re.M).group(1).strip()
    return salida, titulo


def fuente(run_or_style, size=12, mono=False):
    f = run_or_style.font
    nombre = 'Courier New' if mono else FUENTE
    f.name = nombre
    f.size = Pt(size)
    rpr = run_or_style.element.get_or_add_rPr()
    rfonts = rpr.find(qn('w:rFonts'))
    if rfonts is None:
        rfonts = OxmlElement('w:rFonts')
        rpr.append(rfonts)
    for a in ('w:ascii', 'w:hAnsi', 'w:cs', 'w:eastAsia'):
        rfonts.set(qn(a), nombre)
    for a in ('w:asciiTheme', 'w:hAnsiTheme', 'w:cstheme', 'w:eastAsiaTheme'):
        if rfonts.get(qn(a)) is not None:
            del rfonts.attrib[qn(a)]


def formato_parrafo(pf, interlineado=2.0, sangria=None, antes=0, despues=0, alineacion=None):
    pf.line_spacing = interlineado
    pf.space_before = Pt(antes)
    pf.space_after = Pt(despues)
    if sangria is not None:
        pf.first_line_indent = sangria
    if alineacion is not None:
        pf.alignment = alineacion


def ajustar_estilos(doc):
    estilos = {s.name: s for s in doc.styles}
    for nombre in ('Normal', 'Body Text', 'First Paragraph', 'Compact', 'Title', 'Author', 'Date',
                   'Heading 1', 'Heading 2', 'Heading 3', 'Heading 4', 'Heading 5', 'Block Text',
                   'Table Caption', 'Image Caption', 'Captioned Figure', 'Figure'):
        if nombre in estilos:
            e = estilos[nombre]
            fuente(e)
            e.font.color.rgb = RGBColor(0, 0, 0)
            e.font.italic = False
            e.font.bold = False
            formato_parrafo(e.paragraph_format)
    for nombre in ('Body Text', 'First Paragraph'):
        estilos[nombre].paragraph_format.first_line_indent = Inches(0.5)
    estilos['Compact'].paragraph_format.first_line_indent = Inches(0)
    for nivel, centrado, cursiva in ((1, True, False), (2, True, False), (3, False, False), (4, False, True)):
        e = estilos[f'Heading {nivel}']
        e.font.bold = True
        e.font.italic = cursiva
        e.font.size = Pt(12)
        e.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER if centrado else WD_ALIGN_PARAGRAPH.LEFT
        e.paragraph_format.first_line_indent = Inches(0)
        e.paragraph_format.keep_with_next = True
    if 'Source Code' in estilos:
        e = estilos['Source Code']
        fuente(e, 9.5, mono=True)
        formato_parrafo(e.paragraph_format, interlineado=1.0, sangria=Inches(0), antes=6, despues=12)
    for nombre in ('Verbatim Char',):
        if nombre in estilos:
            fuente(estilos[nombre], 10, mono=True)
            estilos[nombre].font.color.rgb = RGBColor(0, 0, 0)
    for nombre in ('Hyperlink',):
        if nombre in estilos:
            estilos[nombre].font.color.rgb = RGBColor(0, 0, 0)
            estilos[nombre].font.underline = False


def borde(padre, lado, valor='single', grosor=8):
    b = OxmlElement(f'w:{lado}')
    b.set(qn('w:val'), valor)
    b.set(qn('w:sz'), str(grosor))
    b.set(qn('w:space'), '0')
    b.set(qn('w:color'), '000000')
    padre.append(b)


def tabla_apa(tabla):
    tblpr = tabla._tbl.tblPr
    for viejo in tblpr.findall(qn('w:tblBorders')):
        tblpr.remove(viejo)
    bordes = OxmlElement('w:tblBorders')
    for lado, val in (('top', 'single'), ('left', 'nil'), ('bottom', 'single'), ('right', 'nil'),
                      ('insideH', 'nil'), ('insideV', 'nil')):
        borde(bordes, lado, val)
    tblpr.append(bordes)
    ancho = tblpr.find(qn('w:tblW'))
    if ancho is None:
        ancho = OxmlElement('w:tblW')
        tblpr.append(ancho)
    ancho.set(qn('w:type'), 'pct')
    ancho.set(qn('w:w'), '5000')
    for i, fila in enumerate(tabla.rows):
        trpr = fila._tr.get_or_add_trPr()
        cant = OxmlElement('w:cantSplit')
        trpr.append(cant)
        for celda in fila.cells:
            if i == 0:
                tcpr = celda._tc.get_or_add_tcPr()
                bs = OxmlElement('w:tcBorders')
                borde(bs, 'bottom')
                tcpr.append(bs)
            for p in celda.paragraphs:
                formato_parrafo(p.paragraph_format, interlineado=1.0, sangria=Inches(0), antes=2, despues=2)
                if i == 0:
                    p.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
                    p.paragraph_format.keep_with_next = True
                for r in p.runs:
                    mono = r.style is not None and r.style.name == 'Verbatim Char'
                    fuente(r, 9.5 if mono else 10.5, mono=mono)
    if len(tabla.rows) <= 14:
        for fila in tabla.rows[:-1]:
            for celda in fila.cells:
                for p in celda.paragraphs:
                    p.paragraph_format.keep_with_next = True


def campo_pagina(parrafo):
    run = parrafo.add_run()
    fuente(run)
    for tipo, texto in (('begin', None), (None, 'PAGE'), ('end', None)):
        if tipo:
            el = OxmlElement('w:fldChar')
            el.set(qn('w:fldCharType'), tipo)
        else:
            el = OxmlElement('w:instrText')
            el.set(qn('xml:space'), 'preserve')
            el.text = texto
        run._r.append(el)


def portada(doc, titulo):
    cuerpo = doc.element.body
    primero = cuerpo[0]
    lineas = [('', False)] * 3 + [(titulo, True), ('', False)] + [(', '.join(AUTORES[:-1]) + ' y ' + AUTORES[-1], False),
              (PROGRAMA, False), (CURSO, False)] + ([(DOCENTE, False)] if DOCENTE else []) + [(FECHA, False)]
    for texto, negrita in lineas:
        p = doc.add_paragraph()
        formato_parrafo(p.paragraph_format, sangria=Inches(0), alineacion=WD_ALIGN_PARAGRAPH.CENTER)
        if texto:
            r = p.add_run(texto)
            r.bold = negrita
            fuente(r)
        primero.addprevious(p._p)
    salto = doc.add_paragraph()
    salto.add_run().add_break(WD_BREAK.PAGE)
    primero.addprevious(salto._p)


ORDEN_PPR = ['pStyle', 'keepNext', 'keepLines', 'pageBreakBefore', 'framePr', 'widowControl', 'numPr',
             'suppressLineNumbers', 'pBdr', 'shd', 'tabs', 'suppressAutoHyphens', 'kinsoku', 'wordWrap',
             'overflowPunct', 'topLinePunct', 'autoSpaceDE', 'autoSpaceDN', 'bidi', 'adjustRightInd', 'snapToGrid',
             'spacing', 'ind', 'contextualSpacing', 'mirrorIndents', 'suppressOverlap', 'jc', 'textDirection',
             'textAlignment', 'textboxTightWrap', 'outlineLvl', 'divId', 'cnfStyle', 'rPr', 'sectPr', 'pPrChange']
ORDEN_TBLPR = ['tblStyle', 'tblpPr', 'tblOverlap', 'bidiVisual', 'tblStyleRowBandSize', 'tblStyleColBandSize',
               'tblW', 'jc', 'tblCellSpacing', 'tblInd', 'tblBorders', 'shd', 'tblLayout', 'tblCellMar', 'tblLook',
               'tblCaption', 'tblDescription']

ORDEN_RPR = ['rStyle', 'rFonts', 'b', 'bCs', 'i', 'iCs', 'caps', 'smallCaps', 'strike', 'dstrike', 'outline',
             'shadow', 'emboss', 'imprint', 'noProof', 'snapToGrid', 'vanish', 'webHidden', 'color', 'spacing', 'w',
             'kern', 'position', 'sz', 'szCs', 'highlight', 'u', 'effect', 'bdr', 'shd', 'fitText', 'vertAlign',
             'rtl', 'cs', 'em', 'lang', 'eastAsianLayout', 'specVanish', 'oMath']
ORDEN_TCPR = ['cnfStyle', 'tcW', 'gridSpan', 'hMerge', 'vMerge', 'tcBorders', 'shd', 'noWrap', 'tcMar',
              'textDirection', 'tcFitText', 'vAlign', 'hideMark']
ORDEN_ESTILO = ['name', 'aliases', 'basedOn', 'next', 'link', 'autoRedefine', 'hidden', 'uiPriority', 'semiHidden',
                'unhideWhenUsed', 'qFormat', 'locked', 'personal', 'personalCompose', 'personalReply', 'rsid',
                'pPr', 'rPr', 'tblPr', 'trPr', 'tcPr', 'tblStylePr']


def reordenar(el, orden):
    pos = {qn('w:' + n): i for i, n in enumerate(orden)}
    hijos = list(el)
    hijos.sort(key=lambda h: pos.get(h.tag, len(orden)))
    for h in hijos:
        el.remove(h)
    for h in hijos:
        el.append(h)


def ordenar_xml(doc):
    estilos = doc.styles.element
    for est in estilos.iter(qn('w:style')):
        reordenar(est, ORDEN_ESTILO)
    for raiz in (estilos, doc.element.body, doc.sections[0].header._element):
        for ppr in raiz.iter(qn('w:pPr')):
            reordenar(ppr, ORDEN_PPR)
        for rpr in raiz.iter(qn('w:rPr')):
            rpr.text = None
            reordenar(rpr, ORDEN_RPR)
    cuerpo = doc.element.body
    for tcpr in cuerpo.iter(qn('w:tcPr')):
        reordenar(tcpr, ORDEN_TCPR)
    for ppr in cuerpo.iter(qn('w:pPr')):
        reordenar(ppr, ORDEN_PPR)
    for tblpr in cuerpo.iter(qn('w:tblPr')):
        reordenar(tblpr, ORDEN_TBLPR)
    for trpr in cuerpo.iter(qn('w:trPr')):
        for extra in trpr.findall(qn('w:cantSplit'))[1:]:
            trpr.remove(extra)
    for pgmar in cuerpo.iter(qn('w:pgMar')):
        pgmar.set(qn('w:footer'), '720')
        pgmar.set(qn('w:gutter'), '0')


def procesar(md, docx_out, ref):
    with tempfile.TemporaryDirectory() as tmp:
        entrada, titulo = preparar_md(md, tmp)
        recursos = os.path.dirname(os.path.abspath(md))
        subprocess.run(['pandoc', entrada, '-f', 'gfm', '-t', 'docx', '--resource-path', recursos,
                        '--reference-doc', ref, '-o', docx_out], check=True)
    doc = Document(docx_out)
    ajustar_estilos(doc)

    sec = doc.sections[0]
    sec.page_width, sec.page_height = Inches(8.5), Inches(11)
    for lado in ('top_margin', 'bottom_margin', 'left_margin', 'right_margin'):
        setattr(sec, lado, Inches(1))
    sec.header_distance = Inches(0.5)
    cab = sec.header.paragraphs[0]
    cab.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    campo_pagina(cab)

    parrafos = doc.paragraphs
    en_referencias = False
    for i, p in enumerate(parrafos):
        texto = p.text.strip()
        estilo = p.style.name
        if estilo.startswith('Heading'):
            en_referencias = texto == 'Referencias'
            if en_referencias:
                p.paragraph_format.page_break_before = True
            continue
        if estilo == 'Source Code':
            continue
        tiene_imagen = bool(p._p.findall('.//' + qn('w:drawing')))
        if tiene_imagen:
            formato_parrafo(p.paragraph_format, interlineado=1.0, sangria=Inches(0), despues=12,
                            alineacion=WD_ALIGN_PARAGRAPH.CENTER)
            for forma in p._p.iter(qn('wp:extent')):
                cx, cy = int(forma.get('cx')), int(forma.get('cy'))
                escala = min(1.0, Inches(6.0) / cx, Inches(4.3) / cy)
                forma.set('cx', str(int(cx * escala)))
                forma.set('cy', str(int(cy * escala)))
                for ext in p._p.iter(qn('a:ext')):
                    ext.set('cx', str(int(cx * escala)))
                    ext.set('cy', str(int(cy * escala)))
            continue
        if re.fullmatch(r'(Tabla|Figura) \d+', texto):
            formato_parrafo(p.paragraph_format, sangria=Inches(0), antes=12)
            p.paragraph_format.keep_with_next = True
            if i + 1 < len(parrafos):
                sig = parrafos[i + 1].paragraph_format
                sig.first_line_indent = Inches(0)
                sig.keep_with_next = True
            continue
        if texto.startswith('Nota.'):
            p.paragraph_format.first_line_indent = Inches(0)
            continue
        if p._p.pPr is not None and p._p.pPr.numPr is not None:
            p.paragraph_format.first_line_indent = Inches(0)
            continue
        if en_referencias and texto:
            p.paragraph_format.first_line_indent = Inches(-0.5)
            p.paragraph_format.left_indent = Inches(0.5)
    for t in doc.tables:
        tabla_apa(t)
        sig = t._tbl.getnext()
        while sig is not None and sig.tag in (qn('w:bookmarkStart'), qn('w:bookmarkEnd')):
            sig = sig.getnext()
        if sig is not None and sig.tag == qn('w:p'):
            ppr = sig.get_or_add_pPr()
            esp = ppr.find(qn('w:spacing'))
            if esp is None:
                esp = OxmlElement('w:spacing')
                ppr.append(esp)
            esp.set(qn('w:before'), '360')
    portada(doc, titulo)
    ordenar_xml(doc)
    doc.core_properties.title = titulo
    doc.core_properties.author = '; '.join(AUTORES)
    doc.save(docx_out)
    print('Word generado (APA 7):', docx_out)


def main():
    raiz = os.path.abspath(os.path.join(AQUI, '..', '..'))
    os.chdir(raiz)
    os.makedirs('docs/word', exist_ok=True)
    with tempfile.TemporaryDirectory() as tmp:
        ref = os.path.join(tmp, 'ref.docx')
        with open(ref, 'wb') as f:
            f.write(subprocess.run(['pandoc', '--print-default-data-file', 'reference.docx'],
                                   check=True, capture_output=True).stdout)
        for md, out in DOCS:
            procesar(md, out, ref)


if __name__ == '__main__':
    sys.exit(main())
