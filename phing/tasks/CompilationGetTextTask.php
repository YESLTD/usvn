<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

require_once "phing/Task.php";

class CompilationGetTextTask extends Task {

    private $locale_directory = "locale";


    /**
     * The init method: Do init steps.
     */
    public function init() {
      // nothing to do here
    }

    /**
    *
    * @param string root of local directory. By default it's locale
    */
	function setLocaledirectory($value)
	{
		$this->locale_directory = $value;
	}

    /**
     * The main entry point method.
     */
    public function main() {
       if (!$dh = opendir($this->locale_directory)) {
        	throw new BuildException('Locale directory is not valide.');
       }
        while (($lang = readdir($dh)) !== false) {
            if ($lang[0] != '.') {
                $this->log("Compilation of $lang", PROJECT_MSG_INFO);
                $src = $this->locale_directory.'/'.$lang.'/LC_MESSAGES/messages.po';
                $dst = $this->locale_directory.'/'.$lang.'/LC_MESSAGES/messages.mo';
                if (!is_file($src)) {
                    throw new BuildException("File $src doesn't exist.");
                }
                $this->compilFile($src, $dst);
            }
        }
        closedir($dh);
    }

    private function compilFile($src, $dst)
    {
        $command = "msgfmt $src --output=$dst";
        $message = array();
        $ret = 0;
        exec($command, $message, $ret);
        if ($ret) {
            throw new BuildException("File $src compilation error.");
        }
    }
}

?>