<?php


namespace models;


class documentation
{
	public function getManualHtml() {
		$html = <<<HTML
			<h1>The Aidhleags Manual</h1>
			<div class="list-group">
			  <a class="list-group-item list-group-item-action" href="#RDF_data">1. RDF data</a>
			  <a class="list-group-item list-group-item-action" href="#">2.</a>
			</div>
			<hr/>
			<h2 id="RDF_data">1. RDF data</h2>
			<p>The system recognises two distinct kinds of RDF data:</p>
			<div class="list-group">
			  <a class="list-group-item list-group-item-action" href="#RDF_textual_data">1.1. RDF textual data</a>
			  <a class="list-group-item list-group-item-action" href="#RDF_personal_data">1.2. RDF personal data</a>
			</div>
			<hr/>
			<h3 id="RDF_textual_data">1.1. RDF textual data</h3>
			<p>Corpus texts are identified using URIs as follows:</p>
			<p><code>
			&lt;https://dasg.ac.uk/corpus/_1&gt;<br/>
			&lt;https://dasg.ac.uk/corpus/_2&gt;<br/>
			&lt;https://dasg.ac.uk/corpus/_3&gt;<br/>
			...<br/>
			&lt;https://dasg.ac.uk/corpus/_329&gt;
			</code></p>
			<p>We will always make use of the RDF prefix <code>c:</code> defined as follows:</p>
			<p><code>
			@prefix c: &lt;https://dasg.ac.uk/corpus/&gt;
			</code></p>
			<p>This means we can identify texts more concisely as follows:</p>
			<p><code>
			c:_1<br/>
			c:_2<br/>
			c:_3<br/>
			...<br/>
			c:_329
			</code></p>
			<p>Subtexts are also identified using URIs:</p>
			<p><code>
			c:_1-1<br/>
			c:_1-2<br/>
			c:_1-3<br/>
			...<br/>
			c:_1-61
			</code></p>
			<p>This also allows for identification of sub-subtexts, sub-sub-subtexts, etc:</p>
			<p><code>
			c:_1-1-1<br/>
			c:_1-1-1-1<br/>
			...
			</code></p>
			<p>Subtexts are related to their texts using the standard Dublin Core predicate <code>dc:isPartOf</code>:</p>
			<p><code>
			c:_1-1 dc:isPartOf c:_1<br/>
			c:_1-1-1 dc:isPartOf c:_1-1<br/>
			...
			</code></p>
			<p>Note that we <strong>never</strong> use the inverse Dublin Core predicate <code>dc:hasPart</code>.</p>
			<p>We also use the following standard Dublin Core predicates to describe corpus texts (and subtexts):</p>
			<table class="table">
			  <thead>
			    <tr><th>predicate</th><th>object</th><th>description</th><th>note</th></tr>
			  </thead>
			  <tbody>
			    <tr><td><code>dc:title</code></td><td><code>string</code></td><td>A reasonably lengthed version of the title of the text</td><td>obligatory, unique</td></tr>
			    <tr><td><code>dc:date</code></td><td><code>date</code></td><td>The year the text was published</td><td>optional, unique; inheritable from supertext if needed; within double quotes</td></tr>
			    <tr><td><code>dc:publisher</code></td><td><code>string</code></td><td>The publisher of the text, including place</td><td>optional, unique; inherited from supertext if needed</td></tr>
			    <tr><td><code>dc:creator</code></td><td><code>uri</code></td><td>The URI of a person who created the Gaelic text, i.e. writer or translator</td><td>optional, non-unique; inheritable from supertext if needed</td></tr>
			    <tr><td><code>dc:identifier</code></td><td><code>int</code></td><td>An integer used to identify the position of the text in the corpus or supertext</td><td>obligatory, unique; no double quotes.</td></tr>
			    <tr><td><code>dc:contributor</code></td><td><code>string</code></td><td>The name of a person who contributed towards the text, e.g. editor or writer of original text translated into Gaelic</td><td>optional, non-unique; inheritable from supertext if needed</td></tr>
			  <tbody>
			</table>
			<p>We also use some project internal predicates, using the default prefix <code>@prefix : &lt;https://dasg.ac.uk/meta/&gt;</code>:</p>
			<table class="table">
			  <thead>
			    <tr><th>predicate</th><th>object</th><th>description</th><th>note</th></tr>
			  </thead>
			  <tbody>
			    <tr><td><code>:fullTitle</code></td><td><code>string</code></td><td>The full version of the title of the (macro) text as specified in the original Text Manual</td><td></td></tr>
			    <tr><td><code>:shortTitle</code></td><td><code>string</code></td><td>The short version of the title of the (macro) text as specified in the original Text Manual</td><td>Within double quotes</td></tr>
			    <tr><td><code>:medium</code></td><td><code>prose | verse | other</code></td><td></td><td>optional, unique; inheitable from supertext if needed; needs thought</td></tr>
			    <tr><td><code>:genre</code></td><td><code>literature | information</code></td><td></td><td>optional, unique; inheritable from supertext if needed; needs thought</td></tr>
			    <tr><td><code>:internalDate</code></td><td><code>date</code></td><td>The year of language as specified in the Editorial Guidelines</td><td>optional, unique; inheritable from supertext if needed</td></tr>
			    <tr><td><code>:rating</code></td><td><code>A | B | C</code></td><td>The rating of the (macro) text as specified in the original Text Manual</td><td>optional, unique; inheritable from the supertext if needed; needs thought</td></tr>
			    <tr><td><code>:xml</code></td><td><code>?</code></td><td></td><td></td></tr>
			  <tbody>
			</table>
			<p>An example chunk of RDF text metadata:</p>
			<p><code>
			c:_1<br/>
			&nbsp;&nbsp;dc:title "Dàin do Eimhir" ;<br/>
			&nbsp;&nbsp;dc:identifier 1 ;<br/>
			&nbsp;&nbsp;dc:date "2002" ;<br/>
			&nbsp;&nbsp;dc:publisher "Association for Scottish Literary Studies, Glasgow" ;<br/>
			&nbsp;&nbsp;dc:creator p:Sorley_MacLean_1911_1996 ;<br/>
			&nbsp;&nbsp;dc:contributor "Whyte, Christopher (editor)" ;<br/>
			&nbsp;&nbsp;:fullTitle "Somhairle MacGill-Eain: Dàin do Eimhir" ;<br/>
			&nbsp;&nbsp;:shortTitle "Dàin do Eimhir" ;<br/>
			&nbsp;&nbsp;:internalDate "1941" ;<br/>
			&nbsp;&nbsp;:rating "B" .<br/>
			<br/>
			c:_1-1<br/>
			&nbsp;&nbsp;dc:title "I" ;<br/>
			&nbsp;&nbsp;dc:identifier 1 ;<br/>
			&nbsp;&nbsp;dc:isPartOf c:_1 ;<br/>
			&nbsp;&nbsp;:genre "literature" ;<br/>
			&nbsp;&nbsp;:medium "verse" ;<br/>
			&nbsp;&nbsp;:xml "1_Daain_do_Eimhir/I.xml" .
			</code></p>
			<p>All of the raw text level RDF metadata is stored in <a href="../rdf/texts.ttl" target="_blank">~/corpas/rdf/texts.ttl</a>.</p>
			<hr/>
			<h3 id="RDF_personal_data">1.2. RDF personal data</h3>
HTML;
		return $html;
	}
}