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
 * and is licensed under the MIT license.
 */

namespace ZfcRbac\View\Strategy;

use Zend\Http\Response as HttpResponse;
use Zend\Mvc\MvcEvent;
use ZfcRbac\Exception\UnauthorizedExceptionInterface;
use ZfcRbac\Options\RedirectStrategyOptions;

/**
 * This strategy redirects to another route when a user is unauthorized
 */
class RedirectStrategy extends AbstractStrategy
{
    /**
     * @var RedirectStrategyOptions
     */
    protected $options;

    /**
     * Constructor
     *
     * @param RedirectStrategyOptions $options
     */
    public function __construct(RedirectStrategyOptions $options)
    {
        $this->options = $options;
    }

    /**
     * @private
     * @param  MvcEvent $event
     * @return void
     */
    public function onError(MvcEvent $event)
    {
        // Do nothing if no error or if response is not HTTP response
        if (!($exception = $event->getParam('exception') instanceof UnauthorizedExceptionInterface)
            || ($result = $event->getResult() instanceof HttpResponse)
            || !($response = $event->getResponse() instanceof HttpResponse)
        ) {
            return;
        }

        $router        = $event->getRouter();
        $redirectRoute = $this->options->getRedirectToRoute();

        $uri = $router->assemble(array(), array('name' => $redirectRoute));

        if ($this->options->getAppendPreviousUri()) {
            $redirectKey = $this->options->getPreviousUriQueryKey();
            $previousUri = $event->getRequest()->getUriString();

            $uri = $router->assemble(
                array(),
                array('name' => $redirectRoute, 'query' => array($redirectKey => $previousUri))
            );
        }

        $response = $event->getResponse() ?: new HttpResponse();

        $response->getHeaders()->addHeaderLine('Location', $uri);
        $response->setStatusCode(302);

        $event->setResponse($response);
        $event->setResult($response);
    }
}
