<?php
/**
 * Created by PhpStorm.
 * User: qwerty
 * Date: 12.01.17
 * Time: 23:37
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActivationToken
 *
 * @ORM\Table(name="activation_token")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ActivationTokenRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ActivationToken
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, unique=true)
     */
    private $token;
    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     */
    private $email;
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set token
     *
     * @param string $token
     *
     * @return ActivationToken
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }
    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
    /**
     * Set email
     *
     * @param string $email
     *
     * @return ActivationToken
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }
    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}
