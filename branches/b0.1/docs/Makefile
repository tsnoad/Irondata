SRS=srs
SDD=sdd
STP=stp
STR=str

target: ${SRS}.dvi

${SRS}.dvi: ${SRS}.tex
	latex $<
	latex $<
	
${SDD}.dvi: ${SDD}.tex
	latex $<
	latex $<

${STP}.dvi: ${STP}.tex
	latex $^
	latex $^

${STR}.dvi: ${STR}.tex
	latex $^
	latex $^

eps:
	dia -n -e eps states/*.dia
	dia -n -e eps cases/*.dia
	dia -n -e eps designs/*.dia

pdf: ${SRS}.dvi ${SDD}.dvi ${STP}.dvi ${STR}.dvi
	dvipdf $^

clean:
	rm *.toc *.dvi *.aux *.log *.pdf states/*.eps cases/*.eps states/*.autosave cases/*.autosave states/*~ cases/*~ designs/*.eps designs/*.autosave designs/*~

