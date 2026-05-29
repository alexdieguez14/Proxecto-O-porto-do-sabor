<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Form\RegistroType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistroController extends AbstractController
{
    /** Crea un empleado nuevo accion solo con rol de administrador */
    #[Route('/admin/empleado/nuevo', name: 'admin_empleado_nuevo')]
    public function registrarEmpleado(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(RegistroType::class, null, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($em->getRepository(Usuario::class)->findOneBy(['email' => $data['email']])) {
                $this->addFlash('error', 'aviso.usuario.existe');
            } else {
                $user = new Usuario();
                $user->setNombre($data['nombre']);
                $user->setApellidos($data['apellidos']);
                $user->setTelefono($data['telefono'] ?: null);
                $user->setEmail($data['email']);
                $user->setRoles([$data['role']]);
                $user->setPassword($hasher->hashPassword($user, $data['password']));
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'aviso.empleado.creado');
                return $this->redirectToRoute('admin_empleado_nuevo');
            }
        }

        return $this->render('admin/empleado/nuevo.html.twig', ['form' => $form]);
    }

    
    #[Route('/registro', name: 'app_registro')]
    public function register(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Si está logueado pero NO es admin, no puede registrar
        if ($this->getUser() && !$isAdmin) {
            return $this->redirectToRoute('app_inicio');
        }

        $form = $this->createForm(RegistroType::class, null, [
            'is_admin' => $isAdmin,
        ]);

        $success = null;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data  = $form->getData();
            $email = $data['email'];
            $role  = $isAdmin ? $data['role'] : 'ROLE_CLIENTE';

            if ($em->getRepository(Usuario::class)->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'aviso.usuario.existe');
            } else {
                $user = new Usuario();
                $user->setNombre($data['nombre']);
                $user->setApellidos($data['apellidos']);
                $user->setTelefono($data['telefono'] !== '' ? $data['telefono'] : null);
                $user->setEmail($email);
                $user->setRoles([$role]);
                $user->setPassword($hasher->hashPassword($user, $data['password']));
                $em->persist($user);
                $em->flush();

                $success = 'Usuario creado correctamente.';
                $form = $this->createForm(RegistroType::class, null, ['is_admin' => $isAdmin]);
            }
        }

        return $this->render('register/index.html.twig', [
            'isAdmin'      => $isAdmin,
            'success'      => $success,
            'form'         => $form,
        ]);
    }
}
